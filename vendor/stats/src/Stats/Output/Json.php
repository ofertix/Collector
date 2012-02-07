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

namespace Stats\Output;

class Json
{
    protected $storage;

    public function __construct($config)
    {
        $this->storage = new $config['class']($config['config']);
    }

    public function getStatEvent($stat, $metric, $round_decimal, $fill_blanks, $date_time_from)
    {
        $result = $this->storage->getStatEvent($stat, $metric, $round_decimal, $fill_blanks, $date_time_from);
        return json_encode($result);
    }
}
