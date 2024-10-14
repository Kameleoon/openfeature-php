<?php
declare(strict_types=1);

namespace Kameleoon;

use Kameleoon\Logging\Logger;
use Kameleoon\Logging\LogLevel;
use Psr\Log\LoggerInterface;

class KameleoonOpenFeatureLogger implements Logger {
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function log($level, string $message): void
    {
        switch ($level) {
            case LogLevel::ERROR:
                $this->logger->error($message);
                break;
            case LogLevel::WARNING:
                $this->logger->warning($message);
                break;
            case LogLevel::INFO:
                $this->logger->info($message);
                break;
            case LogLevel::DEBUG:
                $this->logger->debug($message);
                break;
        }
    }
}
