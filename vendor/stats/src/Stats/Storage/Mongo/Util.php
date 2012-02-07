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

class Util
{
    static public function filterVariableName($name)
    {
        return preg_replace('/^[^a-zA-Z_]+|[^a-zA-Z_0-9]+/', '_', $name);
    }
}