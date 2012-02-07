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

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQ
{
    protected $incoming;

    protected $conn;
    protected $ch;

    public function __construct(array $config, $incoming)
    {
        $this->incoming = $incoming;

        // debug rabbitmq?
        if (isset($config['debug']) && $config['debug'] && !defined('AMQP_DEBUG')) define('AMQP_DEBUG', true);

        // subscriber
        $this->conn = new AMQPConnection($config['host'], $config['port'], $config['user'], $config['pass'], $config['vhost']);
        $this->ch = $this->conn->channel();
        list($queue_name, ,) = $this->ch->queue_declare("", false, false, true, false);
        foreach ($config['exchanges'] as $exchange)
        {
            $this->ch->exchange_declare($exchange, 'fanout', false, false, false);
            $this->ch->queue_bind($queue_name, $exchange);
        }
        $this->ch->basic_consume($queue_name, 'consumer', false, true, false, false, array($this, 'processMessage'));

        //        register_shutdown_function('\Stats\Channel\RabbitMQ::shutdown', $this->ch, $this->conn);
    }

    public function start()
    {
        // Loop as long as the channel has callbacks registered
        while (count($this->ch->callbacks)) {
            $this->ch->wait();
        }

    }

    public function __destruct()
    {
        $this->ch->close();
        $this->conn->close();
    }

    public function processMessage($msg)
    {
        $this->incoming->input($msg->body);
    }
}