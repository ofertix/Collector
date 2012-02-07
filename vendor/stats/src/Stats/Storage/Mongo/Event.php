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

class Event
{
    protected $mongo;
    protected $db;
    protected $autorotate;
    protected $time_to_store_days;

    public function __construct($mongo, $db, $autorotate = false, $time_to_store_days = 365)
    {
        $this->mongo = $mongo;
        $this->db = $db;
        $this->autorotate = $autorotate;
        $this->time_to_store_days = $time_to_store_days;
    }

    public function store($data)
    {
        // persist
        $mongo = $this->mongo;
        $db_name = $this->db;
        $db = $mongo->$db_name;
        $collection_name = 'events';
        $collection = $db->createCollection($collection_name);
        $collection->ensureIndex(array('ts' => 1, 'event' => 1));
        $id = $collection->insert(array(
            'ts' => $ts_mongo = new \MongoDate(strtotime($data['ts'])),
            'event' => $data['event']
        ));

        // delete old data
        if ($this->autorotate) {
            $time_to_store = $this->time_to_store_days * 3600 * 24;
            $ts_old = time() - $time_to_store;
            $collection->remove(array('ts' => array('$lt' => new \MongoDate($ts_old))));
        }

    }

    public function get($min, $max)
    {
        $db_name = $this->db;
        $db = $this->mongo->$db_name;
        // get events
        $cursor = $db->events->find(array(
            'ts' => array('$gte' => new \MongoDate($min), '$lte' => new \MongoDate($max)),
        ));
        $result_events = array();
        foreach ($cursor as $obj)
        {
            $ts = $obj['ts']->sec * 1000;
            $result_events[] = array('x' => $ts, 'title' => $obj['event'][0], 'text' => $obj['event']);
        }

        // result
        return $result_events;
    }
}
