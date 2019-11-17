<?php

namespace RemoteExecuting;

class UpdateCode extends AbstractExecutor
{
    public function getEntryPoint()
    {
        return '/workers/response/update-code';
    }

    public function execute(array $data)
    {
        $output = $this->runCommand('git pull origin master');
        $this->sendResponse(['output' => substr($output, -500)]);
    }
}