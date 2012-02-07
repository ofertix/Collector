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

class Factory
{
    protected $store_stat;
    protected $store_event;

    public function __construct($config_storage)
    {
        $options = array();
        if (isset($config_storage['options'])) $options = $config_storage['options'];
        $mongo = new \Mongo($config_storage['server'], $options);

        $this->store_stat = new Stat($mongo, $config_storage['default_database'], $config_storage['autorotate']);
        $this->store_event = new Event($mongo, $config_storage['default_database'], $config_storage['autorotate'], $config_storage['events_time_to_store']);
    }

    public function saveStat($operations, $retentions, $data)
    {
        $this->store_stat->store($operations, $retentions, $data);
    }

    public function saveEvent($data)
    {
        $this->store_event->store($data);
    }

    public function getStatEvent($stat, $metric, $round_decimal, $fill_blanks, $date_time_from)
    {
        list($result_stats, $min, $max) = $this->store_stat->get($stat, $metric, $round_decimal, $fill_blanks, $date_time_from);
        $result_events = $this->store_event->get($min, $max);

        $result = array();
        $result['stats'] = $result_stats;
        $result['events'] = $result_events;
        return $result;
    }
}


/* TEST a mongo shell *

db.stats_404.find( { ts: "2011-10-27 12:21:41" } ).forEach(
function (e) {

e.raw.sort(function (a,b) { return a - b; });
print(e.raw);
var c = e.raw.length;
var pos = Math.round(c/100*95);
e.percentile_95 = e.raw[pos];

print(e.percentile_95);

db.stats_404.save(e);
});

 */