#!/usr/bin/env php
<?php
// application.php

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class CheckCodeBaseCommand extends Command
{
    /**
     * @var OutputInterface
     */
    private $output;

    protected function configure()
    {
        $this->setName('run');
        $this->addOption(
            'branch',
            'b',
            InputOption::VALUE_REQUIRED,
            'Ветка на которой будут ходить тесты'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $branch = $input->getOption('branch');

        $this->output = $output;
        $classes = [];
        $this->output->writeln('<info>Find all test classes in /test/acceptance directory...</info>');
        $this->walkTree(dirname(__DIR__) . '/tests/acceptance', $classes, 'acceptance');

        $this->output->writeln('<info>Find all test classes in /test/ui directory...</info>');
        $this->walkTree(dirname(__DIR__) . '/tests/ui', $classes, 'ui');

        $this->output->writeln("<comment>Found " . count($classes) ." tests classes.</comment>");

        $this->output->writeln("");
        $this->output->writeln('<info>Push results to TestRunner...</info>');
        $response = $this->pushResultToRunner($classes, $branch);

        if (strpos($response, '200 OK') !== false) {
            $this->output->writeln("<comment>Pushed.</comment>");
        }

        $this->output->writeln("<comment>Response: " . $response . "</comment>");
    }

    private function walkTree($dirPath, &$classes, $testGroup)
    {
        $handle = opendir($dirPath);

        while (false !== ($entry = readdir($handle))) {
            if ($entry != "." && $entry != "..") {
                if (is_dir($dirPath . DIRECTORY_SEPARATOR . $entry)) {
                    $this->walkTree($dirPath . DIRECTORY_SEPARATOR . $entry, $classes, $testGroup);
                } else {
                    $className = $this->getTestClassFromTestFile($dirPath . DIRECTORY_SEPARATOR . $entry, $testGroup);
                    $classes[] = $className;
                    // $this->output->writeln("> " . $className);
                }
            }
        }
        closedir($handle);
    }

    private function getTestClassFromTestFile($fileName, $testGroup)
    {
        $testClass = strstr($fileName, "tests/$testGroup/");
        $testClass = str_replace("tests/$testGroup/", '', $testClass);
        $testClass = str_replace('.php', '', $testClass);
        $testClass = str_replace('/', '\\', $testClass);
        return $testClass;
    }

    private function pushResultToRunner(array $classes, $branch)
    {
        $site = getenv('APP_ENV') == 'development' ? 'http://test-runner.loc' : 'http://test-runner.essay.office';
        $url = $site . '/cases/existing-classes';

        $post = [
            'classes' => $classes,
            'branch' => $branch
        ];

        $config = array(
            'adapter' => 'Zend\Http\Client\Adapter\Curl',
            'curloptions' => array(
                CURLOPT_TIMEOUT_MS => 60 * 10000,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_FOLLOWLOCATION => 0,
            ),
            'maxredirects' => 0,
        );
        $client = new \Zend\Http\Client($url, $config);
        $client->setRawBody(json_encode($post));
        $client->setMethod('POST');
        $client->setHeaders([
            'Content-Type' => 'application/json',
        ]);

        $response = $client->send();

        if ($response->getStatusCode() != 200) {
            $this->output->writeln("<fg=red;bg=default>ERROR!</>");
        }

        return $response->getStatusCode() . " " . $response->getReasonPhrase();
    }
}


$application = new Application();
$application->add(new CheckCodeBaseCommand());
$application->run();
