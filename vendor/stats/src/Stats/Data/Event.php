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

class Event
{
    protected $storage;

    public function __construct($config_storage)
    {
        foreach ($config_storage as $config) $this->storages[] = new $config['class']($config['config']);
    }

    public function input($data)
    {
        foreach ($this->storages as $storage) $storage->saveEvent($data);
    }
}