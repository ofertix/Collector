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

// silex
require_once __DIR__ . '/../../silex/silex.phar';
$app = new Silex\Application();

// load config
$vendorDir = __DIR__ . "/../../";
$app['autoloader']->registerNamespaces(array(
    'Stats' => __DIR__ . "/../src/",
    'Symfony\\Component\\Yaml' => $vendorDir . '/symfony/yaml/',
    'Symfony\\Component\\Console' => $vendorDir . '/symfony/console/',
    'Symfony\\Component\\ClassLoader' => $vendorDir . '/symfony/class-loader/',
));
// routes
$app->match('/stats_events/{stat}/{metric}/{round_decimal}/{fill_blanks}', function ($stat, $metric, $round_decimal, $fill_blanks) use ($app)
{
    $output_storage = $app['config']['output']['storage'];
    $json = new Stats\Output\Json($app['config']['storage'][$output_storage]);

    $date_time_from = null;
    if ($app['request']->get('dateFrom')) $date_time_from = $app['request']->get('dateFrom');
    $result = $json->getStatEvent($stat, $metric, $round_decimal, $fill_blanks, $date_time_from);

    //print_r($result);
    if ($app['request']->get('callback')) {
        $callback = $app['request']->get('callback');
        return $callback . '(' . $result . ');';
    }
    else return $result;
})
    ->value('metric', 'count')
    ->value('round_decimal', 0)
    ->value('fill_blanks', 1);
