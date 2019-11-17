<?php

namespace RemoteExecuting;


class HealthCheck extends AbstractExecutor
{
    public function getEntryPoint()
    {
        return '/workers/response/health-check';
    }

    public function execute(array $data)
    {
        $this->sendResponse('alive');
    }
}