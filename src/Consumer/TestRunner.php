<?php

namespace Consumer;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use Symfony\Component\Process\Process;

class TestRunner extends AbstractConsumer
{
    /**
     * @var \Service\ConsumerTestRunnerBridge
     */
    private $consumerTestRunnerBridge;

    /**
     * @param AMQPStreamConnection $connection
     */
    public function __construct(AMQPStreamConnection $connection, \Service\ConsumerTestRunnerBridge $consumerTestRunnerBridge)
    {
        parent::__construct($connection);
        $this->consumerTestRunnerBridge = $consumerTestRunnerBridge;
    }

    /**
     * @return string
     */
    protected function getQueueName()
    {
        return "auto_tests";
    }

    /**
     * @param array $message
     */
    protected function processMessage(array $message)
    {
        $this->consumerTestRunnerBridge->run($message);
    }
}
