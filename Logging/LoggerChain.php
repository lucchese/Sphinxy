<?php

namespace Brouzie\Sphinxy\Logging;

class LoggerChain implements LoggerInterface
{
    /**
     * @var LoggerInterface[]
     */
    private $loggers;

    public function addLogger(LoggerInterface $logger)
    {
        $this->loggers[] = $logger;
    }

    public function startQuery($sql, array $params = null)
    {
        foreach ($this->loggers as $logger) {
            $logger->startQuery($sql, $params);
        }
    }

    public function stopQuery()
    {
        foreach ($this->loggers as $logger) {
            $logger->stopQuery();
        }
    }
}
