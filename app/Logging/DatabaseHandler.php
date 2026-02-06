<?php

namespace App\Logging;

use App\Models\ErrorLog;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;

class DatabaseHandler extends AbstractProcessingHandler
{
    /**
     * Write the log record to the database.
     */
    protected function write(LogRecord $record): void
    {
        try {
            ErrorLog::create([
                'message' => $record->message,
                'level' => $record->level->getName(),
                'context' => json_encode($record->context),
            ]);
        } catch (\Exception $e) {
            // Silently fail to avoid recursive logging
            // Could log to file instead if needed
        }
    }
}
