<?php

namespace App\Logging;

use Monolog\Handler\RotatingFileHandler;

class CustomDailyLogFormat
{
    public function __invoke($logger)
    {
        foreach ($logger->getHandlers() as $handler) {
            if ($handler instanceof RotatingFileHandler) {
                $handler->setFilenameFormat('laravelog{date}', 'Y-m-d');
            }
        }
    }
}