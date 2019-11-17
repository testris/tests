<?php

namespace Consumer;

use PhpAmqpLib\Connection\AMQPStreamConnection;

class TestRunnerFactory
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

        return new TestRunner($connection, new \Service\ConsumerTestRunnerBridge());
    }
}
