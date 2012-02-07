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

namespace Stats;

use Symfony\Component\Yaml\Yaml;

class Kernel
{
    protected $app;
    protected $configs = array();
    protected $stop = false;
    protected $acumulators = array();
    protected $ch;

    public function __construct(&$app)
    {
        $this->app = &$app;

        \pcntl_signal(SIGTERM, array($this, 'shutdown'));
        \pcntl_signal(SIGINT, array($this, 'shutdown'));
    }

    public function loadConfigFile($fileName)
    {
        $configs = Yaml::parse($fileName);
        $this->checkConfig($configs);
        $this->configs = $configs;

        $this->app['config'] = $configs;
    }

    protected function checkConfig($config)
    {
        //        if (!isset($config['file'])) throw new \Exception('ERR: \'file\'config parameter required.');

        return true;
    }

    public function shutdown()
    {
        exit(); // object destructors will be executed
    }

    public function run()
    {
        $config_storage = $this->app['config']['storage'];
        $config_stats = $this->app['config']['stats'];

        $incoming_class = 'Stats\Data\Incoming';
        if (isset($this->configs['incoming_class'])) $incoming_class = new $this->configs['incoming_class']($config_stats, $config_storage);
        $incoming = new $incoming_class($config_stats, $config_storage);

        $this->ch = new $this->configs['channel']['class']($this->configs['channel']['config'], $incoming);
        $this->ch->start();
    }

}
