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

// usage: php app/collector.php -c app/config/stats_test.yml
//

require __DIR__ . '/../vendor/stats/stats.php';

// run app
$app->run();
