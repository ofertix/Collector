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

namespace Stats\Channel;

class ZeroMQ
{
    protected $incoming;
    protected $subcriber;

    public function initialize(array $config, $incoming)
    {
        $this->incoming = $incoming;

        $context = new \ZMQContext();
        $this->subscriber = new \ZMQSocket($context, \ZMQ::SOCKET_SUB);
        $this->subscriber->setSockOpt(\ZMQ::SOCKOPT_SUBSCRIBE, "");
        //$this->subscriber->setSockOpt(\ZMQ::SOCKOPT_RECOVERY_IVL_MSEC, 1);
        $this->subscriber->setSockOpt(9, 1);
        $this->subscriber->setSockOpt(\ZMQ::SOCKOPT_RATE, 1);
        $this->subscriber->connect($config['socket']['stats']);
        $this->subscriber->connect($config['socket']['events']);

    }

    public function start()
    {
        //        while (!$this->stop) // @TODO: implement stop by kernel
        while (1) // @TODO: implement stop by kernel
        {
            $data = $this->subscriber->recv();
            $this->incoming->input($data);
        }
    }
}