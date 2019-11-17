<?php

namespace Consumer;

use PhpAmqpLib\Message\AMQPMessage;

class RemoteExecuting extends AbstractConsumer
{
    /**
     * @return string
     */
    protected function getQueueName()
    {
        return 'remote_executing';
    }

    public function consume()
    {
        $channel = $this->connection->channel();

        $channel->exchange_declare($this->getQueueName(), 'fanout', false, false, false);

        list($queue_name, ,) = $channel->queue_declare('', false, false, true, false);

        $channel->queue_bind($queue_name, $this->getQueueName());

        $this->log('Waiting for messages. To exit press CTRL+C');

        $callback = function (AMQPMessage $message) {
            try {
                $messageArray = json_decode($message->body, true);

                $this->processMessage($messageArray);
            } catch (\Exception $e) {
                $this->log('Error: ' . $e->getMessage());
                return;
            }

            $this->log('Task finished.');
        };

        $channel->basic_consume($queue_name, '', false, true, false, false, $callback);

        register_shutdown_function(
            function (\PhpAmqpLib\Channel\AMQPChannel $channel, \PhpAmqpLib\Connection\AbstractConnection $connection) {
                $channel->close();
                $connection->close();
            },
            $channel,
            $this->connection
        );

        while (count($channel->callbacks)) {
            $channel->wait();
        }
    }

    /**
     * @param array $message
     */
    protected function processMessage(array $message)
    {
        if (!isset($message['type'])
            || !array_key_exists('payload', $message)
        ) {
            $this->log('Error: Not valid message');
            return;
        }

        $this->log('Receive message: ' . var_export($message, true));
        $this->log('Start process..');

        $executorClass = "RemoteExecuting\\" . $message['type'];

        $this->log('RUN > ' . $executorClass);

        if (class_exists("{$executorClass}Factory")) {
            $factoryClass = "{$executorClass}Factory";

            /** @var \RemoteExecuting\ExecutorInterface $executor */
            $executor = call_user_func("$factoryClass::create");
        } else {
            /** @var \RemoteExecuting\ExecutorInterface $consumer */
            $executor = new $executorClass();
        }

        $executor->execute($message['payload']);

        $this->log('Done.');
    }


    /**
     * @param string $message
     */
    protected function log($message)
    {
        parent::log($message);
    }
}