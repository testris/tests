#!/usr/bin/env php
<?php
// application.php

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\Process;

class RunCodeceptionCommand extends Command
{
    protected function configure()
    {
        $this->setName('run');
        $this->addOption(
            'env',
            'e',
            InputOption::VALUE_REQUIRED,
            'Окружение на котором будут ходить тесты (local, test, staging, mtest, production)'
        );
        $this->addOption(
            'branch',
            'b',
            InputOption::VALUE_OPTIONAL,
            'Ветка на которой будут ходить тесты'
        );
        $this->addOption(
            'group',
            'g',
            InputOption::VALUE_OPTIONAL,
            'Группа тестов. Например тесты только по созданию ордера.'
        );
        $this->addOption(
            'runId',
            null,
            InputOption::VALUE_OPTIONAL,
            'ID run-а в Tests runner'
        );
        $this->addOption(
            'override',
            'o',
            InputOption::VALUE_OPTIONAL,
            'override config'
        );
        $this->addOption(
            'type',
            't',
            InputOption::VALUE_OPTIONAL,
            'type config'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->runCommand("find tests/_output/ -type f -iname \*.png -delete", $output);
        $this->runCommand("find tests/_output/ -type f -iname \*.html -delete", $output);

        $env = $input->getOption('env');
        $branch = $input->getOption('branch');
        $group = $input->getOption('group');
        $runId = $input->getOption('runId');
        $override = $input->getOption('override');
        $type = $input->getOption('type');

        // Hack for Symfony\Component\Console\Command\Command bug fix
        $group = preg_replace('~].+$~', ']', $group);

        if (empty($type)) {
            $type = "acceptance";
        }

        $command = "./vendor/bin/codecept run {$type} -o \"modules: config: \Helper\Site: env: $env\" --steps";
        if (!empty($branch)) {
            $command .= " -o \"modules: config: \Helper\Site: branch: $branch\"";
        }
        if (!empty($group)) {
            $command .= " -g test-runner -o \"groups: test-runner: $group\"";
        }
        if (!empty($runId)) {
            $command .= " -o \"extensions: config: RunReporterExtension: runId: $runId\"";
            $command .= " --ext RunReporterExtension";
        }
        if (!empty($override)) {
            $command .= " -o \"$override\"";
        }

        $tests = explode(',', trim($group, ']['));

        $output->writeln('');
        $output->writeln('Command params:');
        $output->writeln('  env = ' . $env);
        $output->writeln('  branch = ' . $branch);
        $output->writeln('  runId = ' . $runId);
        $output->writeln('  tests = [');
        $output->writeln('    ' . implode(PHP_EOL . '    ', $tests));
        $output->writeln('  ]');
        $output->writeln('<info>RUN > ' . $command . '</info>');

        $process = new Process($command);
        $process->setTimeout(0);
        $process->run(function ($type, $buffer) {
            if (Process::ERR === $type) {
                echo 'ERR > '.$buffer;
            } else {
                echo $buffer;
            }
        });

    }

    private function runCommand($command, OutputInterface $output)
    {
        $output->writeln('<info>RUN > ' . $command . '</info>');

        $process = new Process($command);
        $process->setTimeout(0);
        $process->run(function ($type, $buffer) {
            if (Process::ERR === $type) {
                echo 'ERR > '.$buffer;
            } else {
                echo $buffer;
            }
        });
    }
}


$application = new Application();
$application->add(new RunCodeceptionCommand());
$application->run();



