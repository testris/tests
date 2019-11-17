<?php

namespace Consumer;

use PhpAmqpLib\Connection\AMQPStreamConnection;

class WebPerformanceCheckFactory
{
    /**
     * @param array $config
     * @return WebPerformanceCheck
     */
    public static function create($config)
    {
        $connection = new AMQPStreamConnection(
            $config['rabbitMQ']['host'],
            $config['rabbitMQ']['port'],
            $config['rabbitMQ']['username'],
            $config['rabbitMQ']['password']
        );

        return new WebPerformanceCheck($connection, $config['web-performance']);
    }
}