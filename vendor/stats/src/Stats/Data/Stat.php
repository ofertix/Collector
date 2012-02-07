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

class Stat
{
    protected $storages = array();
    protected $config_stats;

    public function __construct($config_stats, $config_storage)
    {
        foreach ($config_storage as $config) $this->storages[] = new $config['class']($config['config']);
        $this->config_stats = $config_stats;
    }

    public function input($data)
    {
        list($operations, $retentions) = $this->getConfig($data);
        foreach ($this->storages as $storage) $storage->saveStat($operations, $retentions, $data);
    }

    protected function getConfig($data)
    {
        // load default config
        $types = $this->config_stats['default_types'];

        // override config
        if (isset($this->config_stats['overrides'])) {
            foreach ($this->config_stats['overrides'] as $override)
            {
                if (preg_match('/' . $override['pattern'] . '/', $data['name'])) $types = array_merge($types, $override['types']);
            }
        }


        // operations for current data
        $operations = array();
        $retentions = array();
        foreach ($data['values'] as $item)
        {
            foreach ($item as $type => $value)
            {
                if (isset($types[$type]['operations'])) foreach ($types[$type]['operations'] as $operation) $operations[] = array($operation => $value);
                else $operations[] = array($type => $value);

                $retentions[] = $types[$type]['retentions'];
            }
        }

        // retentions for current data
        $ret = array();
        foreach ($retentions as $i => $item)
        {
            foreach ($item as $j => $retention)
            {
                list($time_per_point, $time_to_store) = explode(':', $retention);
                $time_per_point = $this->toSeconds($time_per_point);
                $time_to_store = $this->toSeconds($time_to_store);

                $ret[$retention] = array(
                    'name' => str_replace(':', '_', $retention),
                    'time_per_point' => $time_per_point,
                    'time_to_store' => $time_to_store,
                );
            }
        }
        $ret = array_values($ret);

        return array($operations, $ret);
    }

    protected function toSeconds($time)
    {
        if (preg_match('/(.+)s$/', $time, $matches)) return $matches[1];
        if (preg_match('/(.+)m$/', $time, $matches)) return $matches[1] * 60;
        if (preg_match('/(.+)h$/', $time, $matches)) return $matches[1] * 3600;
        if (preg_match('/(.+)d$/', $time, $matches)) return $matches[1] * 3600 * 24;

        throw new \Exception('Time "' . $time . '" can not be converted to seconds.');
    }

}
