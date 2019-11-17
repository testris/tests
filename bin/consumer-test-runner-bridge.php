#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConsumerTestRunnerBridgeCommand extends Command
{
    protected function configure()
    {
        $this->setName('run');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $consumerTestRunnerBridge = new \Service\ConsumerTestRunnerBridge();

        $message = '{"queue":"auto_tests","message":"{\"env\":\"staging\",\"branch\":\"\",\"runId\":\"1145\",\"tests\":[\"BestessaysCom\\\\OrderForm\\\\Resubmit\\\\EmptyResubmitOnPaidOrderCest\"]}"}';
        $message = json_decode($message, true);
        $message = str_replace('\\', '\\\\', $message['message']);
        $message = json_decode($message, true);

        $consumerTestRunnerBridge->run($message);
    }
}


$application = new Application();
$application->add(new ConsumerTestRunnerBridgeCommand());
$application->run();



