<?php

namespace RemoteExecuting;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

abstract class AbstractExecutor
{
    /**
     * @var string
     */
    private $ip;

    public function __construct()
    {
        $this->initIpAddress();
    }

    abstract public function execute(array $data);

    /**
     * @return string
     */
    abstract public function getEntryPoint();

    /**
     * @param mixed $message
     */
    protected function sendResponse($message)
    {
        $post = [
            'ip' => $this->ip,
            'message' => $message,
        ];

        $config = array(
            'adapter' => \Zend\Http\Client\Adapter\Curl::class,
            'curloptions' => array(
                CURLOPT_TIMEOUT_MS => 60 * 10000,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
            ),
        );

        $hostSite = getenv('APP_ENV') == 'development' ? 'http://test-runner.loc' : 'http://test-runner.essay.office';
        $client = new \Zend\Http\Client($hostSite . $this->getEntryPoint(), $config);
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

    /**
     * @param string $command
     * @return string
     */
    protected function runCommand($command)
    {
        echo 'RUN > ' . $command . PHP_EOL;

        $process = new Process($command);
        $process->setTimeout(0);
        $process->run();

        try {
            $process->mustRun();

            $output = $process->getOutput();

        } catch (ProcessFailedException $exception) {
            $output = $process->getErrorOutput();
        }

        echo $output;

        return $output;
    }
}
