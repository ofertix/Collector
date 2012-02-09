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

use Symfony\Component\Yaml\Yaml;

require __DIR__ . '/../vendor/stats/web/bootstrap.php';

$app['config'] = Yaml::parse(__DIR__ . '/../app/config/stats_test.yml');

$app->run();
