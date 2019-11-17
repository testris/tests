<?php

namespace Consumer;

use PhpAmqpLib\Connection\AMQPStreamConnection;

class WebPerformanceCheck extends AbstractConsumer
{
    /**
     * @var array
     */
    private $config;
    /**
     * @param AMQPStreamConnection $connection
     * @param array $config
     */
    public function __construct(AMQPStreamConnection $connection, array $config = [])
    {
        parent::__construct($connection);
        $this->config = $config;
    }

    /**
     * @return string
     */
    protected function getQueueName()
    {
        return "web_performance_check";
    }

    /**
     * @param array $message
     */
    protected function processMessage(array $message)
    {
        if (!isset($message['url'])) {
            $this->log('Error: Not valid message');
            return;
        }

        $localReportsFolder = rtrim($this->config['local-reports-folder'], '/') . DIRECTORY_SEPARATOR;

        $this->log('Receive message: ' . var_export($message, true));
        $this->log('Start process..');

        $outputName = substr(md5(time() . rand(100000, 999999)), 0, 7);

        $command = sprintf(
            'docker run --rm --name lighthouse '
            . '-v %s:/home/chrome/reports '
            . '--cap-add=SYS_ADMIN femtopixel/google-lighthouse %s '
            . '--output json --output html --output-path=./%s.json',
            rtrim($localReportsFolder, '/'),
            $message['url'],
            $outputName
        );

        $this->runCommand($command);

        $outputJsonFileName = $outputName . '.report.json';
        $outputJsonPath = $localReportsFolder . $outputJsonFileName;
        $outputHtmlFileName = $outputName . '.report.html';
        $outputHtmlPath = $localReportsFolder . $outputHtmlFileName;

        $reportJson = file_get_contents($outputJsonPath);
        $result = json_decode($reportJson, true);

        $performance = $result['categories']['performance']['score'] * 100;
        $pwa = $result['categories']['pwa']['score'] * 100;
        $accessibility = $result['categories']['accessibility']['score'] * 100;
        $bestPractices = $result['categories']['best-practices']['score'] * 100;
        $seo = $result['categories']['seo']['score'] * 100;

        $this->log("url=" . $message['url']);
        $this->log("performance=$performance");
        $this->log("pwa=$pwa");
        $this->log("accessibility=$accessibility");
        $this->log("bestPractices=$bestPractices");
        $this->log("seo=$seo");

        $post = [
            "url" => $message['url'],
            "htmlReport" => [
                "folder" => date("Ymd"),
                "fileName" => $outputHtmlFileName,
            ],
            "performance" => (int)$performance,
            "pwa" => (int)$pwa,
            "accessibility" => (int)$accessibility,
            "bestPractices" => (int)$bestPractices,
            "seo" => (int)$seo
        ];

        $config = array(
            'adapter' => 'Zend\Http\Client\Adapter\Curl',
            'curloptions' => array(
                CURLOPT_TIMEOUT_MS => 60 * 10000,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
            ),
        );

        $client = new \Zend\Http\Client($this->config['web-performance-web-server-url'] . "/urls/add-score-result", $config);
        $client->setRawBody(json_encode($post));
        $client->setMethod('POST');
        $client->setHeaders([
            'Content-Type' => 'application/json',
        ]);

        $client->send();

        $this->log("Send results to " . $this->config['web-performance-web-server-url'] . "/urls/add-score-result");
        $this->log(var_export($post, true));

        // for local: cp %s /home/user/devellar/web-performance/public/reports/%s/%s
        // for prod: scp %s 192.168.216.88:/var/www/web-performance.net/public/reports/%s/%s
        $command = sprintf(
            $this->config['reports-saving-command'],
            $outputHtmlPath,
            date("Ymd"),
            $outputHtmlFileName
        );

        $this->runCommand($command);

        unlink($outputJsonPath);
        unlink($outputHtmlPath);
    }
}