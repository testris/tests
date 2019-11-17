#!/usr/bin/env php
<?php
// application.php

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class RunConsumer extends Command
{
    protected function configure()
    {
        $this->setName('run');

        $this->addOption(
            'consumer',
            'c',
            InputOption::VALUE_REQUIRED,
            'Имя консюмера'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $consumerClass = "Consumer\\" . $input->getOption('consumer');

        $output->writeln('');
        $output->writeln('<info>RUN > ' . $input->getOption('consumer') . '</info>');

        if (class_exists("{$consumerClass}Factory")) {
            $factoryClass = "{$consumerClass}Factory";

            /** @var \Consumer\AbstractConsumer $consumer */
            $consumer = call_user_func("$factoryClass::create", $this->getConfig());
        } else {
            /** @var \Consumer\AbstractConsumer $consumer */
            $consumer = new $consumerClass();
        }

        $consumer->consume();
    }

    /**
     * @return array
     */
    private function getConfig()
    {
        $aggregator = new \Zend\ConfigAggregator\ConfigAggregator([
            // Load application config in a pre-defined order in such a way that local settings
            // overwrite global settings. (Loaded as first to last):
            //   - `global.php`
            //   - `*.global.php`
            //   - `local.php`
            //   - `*.local.php`
            new \Zend\ConfigAggregator\PhpFileProvider(dirname(__DIR__) . '/config/{{,*.}global,{,*.}local}.php'),
        ]);

        return $aggregator->getMergedConfig();
    }
}

$application = new Application();
$application->add(new RunConsumer());
$application->run();



