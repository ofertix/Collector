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

use Symfony\Component\ClassLoader\UniversalClassLoader;

class App extends \Pimple
{
    protected $kernel;

    public function __construct()
    {
        $app = $this;

        $this['autoloader'] = $this->share(function ()
        {
            $loader = new UniversalClassLoader();
            $loader->register();

            return $loader;
        });

        $this['debug'] = false;
        $this['charset'] = 'UTF-8';
    }

    protected function getOptionsFromCommandLine()
    {
        $parameters = array(
            'c:' => 'config' // Specify config file
        );

        $config = array();

        $options = getopt(implode('', array_keys($parameters)), $parameters);
        foreach ($options as $option => $value)
        {
            switch ($option)
            {
                case 'c':
                case 'config':
                    if (is_string($value)) {
                        $this->kernel->loadConfigFile($value);
                    } else
                    {
                        echo "ERR: Illegal value for -c or --config\n";
                    }
                    break;
            }
        }
    }

    public function run()
    {
        $this->kernel = new Kernel($this);
        $this->getOptionsFromCommandLine();
        $this->kernel->run();
    }
}