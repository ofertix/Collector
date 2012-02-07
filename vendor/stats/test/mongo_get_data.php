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

$mongo = new Mongo('mongodb://localhost:27017');
$db = $mongo->stats_myproject;

// stats
$cursor = $db->stats_404_1m_1d->find();
$cursor->sort(array('ts' => 1));
foreach ($cursor as $obj)
{
    print_r($obj);
    echo "\n";
    echo date('Y-m-d H:i:s', $obj['ts']->sec);
    echo "\n";
}
