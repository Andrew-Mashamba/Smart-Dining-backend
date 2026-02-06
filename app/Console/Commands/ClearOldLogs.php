<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ClearOldLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'log:clear {--days=30 : Number of days to keep logs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear log files older than specified days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $this->info("Clearing log files older than {$days} days...");

        $logPath = storage_path('logs');
        $cutoffDate = Carbon::now()->subDays($days);

        if (! File::exists($logPath)) {
            $this->error("Log directory does not exist: {$logPath}");

            return 1;
        }

        $files = File::files($logPath);
        $deletedCount = 0;
        $totalSize = 0;

        foreach ($files as $file) {
            $fileModifiedTime = Carbon::createFromTimestamp(File::lastModified($file));

            // Skip the current day's log file
            if ($file->getFilename() === 'laravel.log') {
                continue;
            }

            if ($fileModifiedTime->lt($cutoffDate)) {
                $size = File::size($file);
                $totalSize += $size;

                if (File::delete($file)) {
                    $deletedCount++;
                    $this->line("Deleted: {$file->getFilename()} (".$this->formatBytes($size).')');
                }
            }
        }

        if ($deletedCount > 0) {
            $this->info("Successfully deleted {$deletedCount} log files, freed ".$this->formatBytes($totalSize).' of space.');
        } else {
            $this->info('No old log files found to delete.');
        }

        return 0;
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision).' '.$units[$i];
    }
}
