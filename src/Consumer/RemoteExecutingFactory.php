<?php

namespace Consumer;

use PhpAmqpLib\Connection\AMQPStreamConnection;

class RemoteExecutingFactory
{
    /**
     * @param array $config
     * @return TestRunner
     */
    public static function create($config)
    {
        $connection = new AMQPStreamConnection(
            $config['rabbitMQ']['host'],
            $config['rabbitMQ']['port'],
            $config['rabbitMQ']['username'],
            $config['rabbitMQ']['password']
        );

        return new RemoteExecuting($connection);
    }
}