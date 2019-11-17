<?php

namespace Consumer;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use Symfony\Component\Process\Process;

abstract class AbstractConsumer
{
    /**
     * @var AMQPStreamConnection
     */
    protected $connection;

    /**
     * @param AMQPStreamConnection $connection
     */
    public function __construct(AMQPStreamConnection $connection)
    {
        $this->connection = $connection;
    }

    public function consume()
    {
        $connection = $this->connection->getConnection();
        $channel = $connection->channel();

        $channel->queue_declare($this->getQueueName(), false, true, false, false);

        $this->log('Waiting for messages. To exit press CTRL+C');

        $callback = function($message) {
            try {
                $messageArray = json_decode($message->body, true);

                $this->processMessage($messageArray);
            } catch (\Exception $e) {
                $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
                $this->log('Error: ' . $e->getMessage());
                return;
            }

            $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
            $this->log("Task finished.");
        };

        $channel->basic_qos(null, 1, null);
        $channel->basic_consume($this->getQueueName(), '', false, false, false, false, $callback);

        register_shutdown_function(
            function (\PhpAmqpLib\Channel\AMQPChannel $channel, \PhpAmqpLib\Connection\AbstractConnection $connection) {
                $channel->close();
                $connection->close();
            },
            $channel,
            $connection
        );

        while (count($channel->callbacks)) {
            $channel->wait();
        }
    }

    /**
     * @return string
     */
    abstract protected function getQueueName();

    /**
     * @param array $message
     * @return void
     */
    abstract protected function processMessage(array $message);

    /**
     * @param string $command
     */
    protected function runCommand($command)
    {
        $this->log('RUN > ' . $command . PHP_EOL);
        $process = new Process($command);
        $process->setTimeout(0);
        $that = $this;
        $process->run(function ($type, $buffer) use($that) {
            $output = trim($buffer) . PHP_EOL;

            if (Process::ERR === $type) {
                $output = 'ERR > ' . $output;
            }

            echo $output;
        });
    }

    /**
     * @param string $message
     */
    protected function log($message)
    {
        $message = sprintf(
            "[%s] %s",
            date("Y-m-d H:i:s"),
            $message . PHP_EOL
        );
        echo $message;
    }
}