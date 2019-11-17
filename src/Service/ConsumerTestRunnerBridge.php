<?php

namespace Service;

use Symfony\Component\Process\Process;

class ConsumerTestRunnerBridge
{
    /**
     * @var int
     */
    private $runId;

    /**
     * @var string
     */
    private $ip;


    public function __construct()
    {
        $this->initIpAddress();
    }

    /**
     * @param array $options
     */
    public function run(array $options)
    {
        if (!isset($options['env'])
            || !array_key_exists('branch', $options)
            || !isset($options['tests'])
            || !is_array($options['tests'])
            || !isset($options['runId'])
        ) {
            $this->log('Error: Not valid message');
            return;
        }
        
        if (!isset($options['type']) || !$options['type']) {
            $options['type'] = 'acceptance';
        }

        $this->log('Receive message: ' . var_export($options, true));
        $this->log('Start process..');

        $this->runId = $options['runId'];

        $command = "git pull";
        $this->runCommand($command);

        $command = "composer install";
        $this->runCommand($command);

        if (!empty($options['branch'])) {
            $command = sprintf(
                "./bin/test-codebase.php run -b %s -t %s",
                escapeshellarg($options['branch']),
                escapeshellarg($options['type'])
            );;
            $this->runCommand($command);
        }

        $tests = [];
        foreach ($options['tests'] as $test) {
            $tests[] = "tests/{$options['type']}/" . str_replace('\\', '/', $test);
        }

        $testsString = implode(',', $tests);

        if (!empty($options['branch'])) {
            $command = sprintf(
                "./bin/test-runner.php run -e %s -b %s -t %s --runId %s -g [%s]",
                escapeshellarg($options['env']),
                escapeshellarg($options['type']),
                escapeshellarg($options['branch']),
                escapeshellarg($this->runId),
                $testsString
            );
        } else {
            // for staging
            $command = sprintf(
                "./bin/test-runner.php run -e %s -t %s --runId %s -g [%s]",
                escapeshellarg($options['env']),
                escapeshellarg($options['type']),
                escapeshellarg($this->runId),
                $testsString
            );
        }
        $this->runCommand($command);
    }

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

            if (empty($output)) {
                return;
            }

            if (Process::ERR === $type) {
                $output = 'ERR > ' . $output;
            }

            $that->sendOutput($output, $that->runId);

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

        $this->sendOutput($message, $this->runId);
    }

    /**
     * @param string $output
     * @param int $runId
     */
    private function sendOutput($output, $runId)
    {
        if (!$runId) {
            return;
        }

        $post = [
            'ip' => $this->ip,
            'output' => $output,
        ];

        $config = array(
            'adapter' => 'Zend\Http\Client\Adapter\Curl',
            'curloptions' => array(
                CURLOPT_TIMEOUT_MS => 60 * 10000,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
            ),
        );

        $hostSite = getenv('APP_ENV') == 'development' ? 'http://test-runner.loc' : 'http://test-runner.essay.office';

        $client = new \Zend\Http\Client("{$hostSite}/test-run/$runId/save-output", $config);
        $client->setRawBody(json_encode($post));
        $client->setMethod('POST');
        $client->setHeaders([
            'Content-Type' => 'application/json',
        ]);

        $client->send();
    }

    private function initIpAddress()
    {
        // еще вот так можно
        // for i in `find /sys/class/net -type l -not -lname '*virtual*' -printf '%f\n'`; do ifconfig $i | grep 'inet addr:' | cut -d: -f2| cut -d' ' -f1; done
        $process = new Process("hostname -I");
        // $process->setTimeout(0);
        $that = $this;
        $process->run(function ($type, $buffer) use ($that) {
            $output = trim($buffer);
            $matches = explode(' ', $output);

            if (isset($matches[0])) {
                $that->ip = $matches[0];
            }
        });
    }
}
