<?php

/*
 * This file is part of the collector package.
 *
 * (c) Joan Valduvieco <joan.valduvieco@ofertix.com>
 * (c) Jordi Llonch <jordi.llonch@ofertix.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stats\Storage\Mongo;

class Stat
{
    protected $mongo;
    protected $db;
    protected $autorotate = false;
    protected $autoclean_raw_data_time = 2; // hours before current data ts

    protected $work_todo = array();
    protected $work_todo_do_work_every_n_seconds = 60;
    protected $work_todo_current_time;

    public function __construct($mongo, $db, $autorotate = false)
    {
        $this->mongo = $mongo;
        $this->db = $db;
        $this->autorotate = $autorotate;
        $this->work_todo_current_time = time();
    }

    public function __destruct()
    {
        $this->doWorkTodo(true);
    }

    public function store($operations, $retentions, $data)
    {
        //        print_r($operations);
        //        print_r($retentions);
        //        print_r($data);

        // operations
        $op = array();
        $post_op = array();
        foreach ($operations as $item)
        {
            $item = each($item);
            $operation = $item['key'];
            $value = (float)$item['value'];

            switch ($operation)
            {
                case 'count':
                    $op['$inc'] = array('count' => $value);
                    break;
                case 'median':
                    $op['$push'] = array('raw' => $value);
                    $post_op[] = '
var s = 0;
for(var i in e.raw)
{
  s = s + e.raw[i];
}
e.median = s / e.raw.length;
';
                    break;
                case 'percentile_95':
                    $op['$push'] = array('raw' => $value);
                    $post_op[] = '
e.raw.sort(function (a,b) { return a - b; });
var c = e.raw.length;
var pos = Math.round(c/100*95)-1;
e.percentile_95 = e.raw[pos];
';
                    break;
                case 'max':
                    $post_op[] = '
var m = e.raw[0];
for(var i in e.raw)
{
  if(e.raw[i] > m) m = e.raw[i];
}
e.max = m;
';
                    break;
                case 'min':
                    $post_op[] = '
var m = e.raw[0];
for(var i in e.raw)
{
  if(e.raw[i] < m) m = e.raw[i];
}
e.min = m;
';
                    break;
            }
        }

        // persist
        $db = $this->getDb();

        foreach ($retentions as $retention)
        {
            $collection_name = Util::filterVariableName('stats_' . $data['name'] . '_' . $retention['name']);

            //$number_elements = $retention['time_per_point'] * $retention['time_to_store'];
            $list = $db->listCollections();
            $list_collections = array();
            $collection = $db->createCollection($collection_name);
            $collection->ensureIndex(array('ts' => 1), array("unique" => true));

            $ts = strtotime($data['ts']);
            $ts = floor($ts / $retention['time_per_point']) * $retention['time_per_point'];
            $ts_mongo = new \MongoDate($ts);

            $op['$set'] = array('ts' => $ts_mongo);

            $id = $collection->update(
                array('ts' => $ts_mongo),
                $op,
                array('upsert' => true)
            );
            //            var_dump($db->lastError());

            // cache work to do every N seconds
            // calculate
            if (count($post_op)) {
                //                $ts = $ts * 1000;
                //                $response = $db->execute("db.$collection_name.find( { ts: new Date($ts) } ).forEach(
                //function (e) {
                //" . implode("\n", $post_op) . "
                //db.$collection_name.update({_id : e._id}, e);
                //});");

                $ts = $ts * 1000;
                if (!isset($this->work_todo['calculates'][$collection_name][$ts])) {
                    $this->work_todo['calculates'][$collection_name][$ts] = "db.$collection_name.find( { ts: new Date($ts) } ).forEach(
function (e) {
" . implode("\n", $post_op) . "
db.$collection_name.update({_id : e._id}, e);
});";
                }
            }

            // cache work to do every N seconds
            // delete old data
            $this->work_todo['autorotate'][$collection_name] = $retention['time_to_store'];

//            $this->doWorkTodo();
            $this->doWorkTodo(true);
        }
    }

    protected function getDb()
    {
        $mongo = $this->mongo;
        $db_name = $this->db;
        $db = $mongo->$db_name;
        return $db;
    }

    protected function doWorkTodo($force = false)
    {
        // do work every N seconds
        if ($force || time() - $this->work_todo_current_time >= $this->work_todo_do_work_every_n_seconds) {
            // do calculate
            $this->doCalculates();

            // do autorotate
            $this->doAutorotate();

            $this->work_todo_current_time = time();
        }
    }

    protected function doCalculates()
    {
        if (!isset($this->work_todo['calculates'])) return;

        $db = $this->getDb();
        foreach ($this->work_todo['calculates'] as $collection_name => $items)
        {
            foreach ($items as $ts => $item)
            {
                $response = $db->execute($item);

                // autoclean old raw data
                $this->doAutocleanRawData($ts, $collection_name, $db);
            }
        }

        // clear calculates to do
        $this->work_todo['calculates'] = array();
    }

    protected function doAutocleanRawData($ts, $collection_name, $db)
    {
        $ts_mongo = new \MongoDate(($ts / 1000) - (3600 * $this->autoclean_raw_data_time));
        $db->$collection_name->update(
            array(
                'ts' => array('$lte' => $ts_mongo),
                'raw' => array('$exists' => true)
            ),
            array('$unset' => array('raw' => 1)),
            array('multiple' => true)
        );
    }

    protected function doAutorotate()
    {
        if (!isset($this->work_todo['autorotate'])) return;

        // delete old data
        if ($this->autorotate) {
            $db = $this->getDb();
            foreach ($this->work_todo['autorotate'] as $collection_name => $time_to_store)
            {
                $collection = $db->$collection_name;
                $ts_old = time() - $time_to_store;
                $collection->remove(array('ts' => array('$lt' => new \MongoDate($ts_old))));
            }

            // clear autorotate to do
            $this->work_todo['autorotate'] = array();
        }
    }

    public function get($stat, $metric = 'count', $round_decimal = 0, $fill_blanks = true, $date_time_from = null)
    {
        $db_name = $this->db;
        $db = $this->mongo->$db_name;

        // stats
        $stat = Util::filterVariableName('stats_' . $stat);
        if ($date_time_from == null) $cursor = $db->$stat->find();
        else $cursor = $db->$stat->find(array(
            'ts' => array('$gte' => new \MongoDate(strtotime($date_time_from))),
        ));
        $cursor->sort(array('ts' => 1));
        $min = null;
        $max = null;
        $r = array();
        foreach ($cursor as $obj)
        {
            if (isset($obj[$metric])) {
                //            print_r($obj);
                //            echo "\n";
                $ts = $obj['ts']->sec * 1000;
                $r[$ts] = round($obj[$metric], $round_decimal);
                //            $r[] = array($ts, $obj['count']);

                if ($ts < $min || $min == null) $min = $ts;
                if ($ts > $max || $max == null) $max = $ts;
            }
        }
        //        print_r($r);

        // fill blanks
        $stat_parts = explode('_', $stat);
        $time = $stat_parts[count($stat_parts) - 2];
        if (preg_match('/(.+)s$/', $time, $matches)) $time = $matches[1];
        if (preg_match('/(.+)m$/', $time, $matches)) $time = $matches[1] * 60;
        if (preg_match('/(.+)h$/', $time, $matches)) $time = $matches[1] * 3600;
        if (preg_match('/(.+)d$/', $time, $matches)) $time = $matches[1] * 3600 * 24;

        // fill blanks
        $result_stats = array();
        if ($min != null && $max != null) {
            for ($i = $min; $i <= $max; $i = $i + ($time * 1000))
            {
                if (isset($r[$i])) $result_stats[] = array($i, $r[$i]);
                else if ($fill_blanks) $result_stats[] = array($i, 0);
            }
        }

        // result
        $min = $min / 1000;
        $max = $max / 1000;
        return array($result_stats, $min, $max);
    }
}
