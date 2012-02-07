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

namespace Stats\Data;

class Incoming
{
    protected $stat;
    protected $event;

    public function __construct($config_stats, $config_storage)
    {
        $this->stat = new Stat($config_stats, $config_storage);
        $this->event = new Event($config_storage);
    }

    public function input($data_raw)
    {
        $data = json_decode($data_raw, true);
        //print_r($data);
        //echo "\n";
        if (isset($data['event'])) $this->event->input($data);
        if (isset($data['name'])) $this->stat->input($data);

        pcntl_signal_dispatch();
    }
}