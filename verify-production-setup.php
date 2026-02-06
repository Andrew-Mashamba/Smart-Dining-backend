<?php

/**
 * Production Environment Configuration Verification Script
 * Story 50: Setup production environment configuration
 *
 * This script verifies all acceptance criteria are met for production deployment.
 *
 * Usage: php verify-production-setup.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

class ProductionSetupVerifier
{
    private array $results = [];

    private int $passed = 0;

    private int $failed = 0;

    private int $warnings = 0;

    public function run(): void
    {
        echo "\n".str_repeat('=', 80)."\n";
        echo "Production Environment Configuration Verification\n";
        echo "Story 50: Setup production environment configuration\n";
        echo str_repeat('=', 80)."\n\n";

        $this->verifyEnvironmentFile();
        $this->verifySecuritySettings();
        $this->verifyHTTPSForcing();
        $this->verifyQueueConfiguration();
        $this->verifySupervisorConfig();
        $this->verifyCronConfiguration();
        $this->verifyScheduledTasks();
        $this->verifyStorageLink();
        $this->verifyFilePermissions();
        $this->verifyLogging();
        $this->verifyReverbConfiguration();
        $this->verifyBackupConfiguration();
        $this->verifyDocumentation();

        $this->printSummary();
    }

    private function verifyEnvironmentFile(): void
    {
        $this->section('1. Environment File (.env.production)');

        $envProductionPath = __DIR__.'/.env.production';

        if (file_exists($envProductionPath)) {
            $this->pass('.env.production file exists');

            $content = file_get_contents($envProductionPath);

            // Check critical settings
            $this->check(
                str_contains($content, 'APP_ENV=production'),
                'APP_ENV set to production',
                'APP_ENV not set to production'
            );

            $this->check(
                str_contains($content, 'APP_DEBUG=false'),
                'APP_DEBUG set to false',
                'APP_DEBUG not set to false (SECURITY RISK!)'
            );

            $this->check(
                str_contains($content, 'DB_CONNECTION='),
                'Database configuration present',
                'Database configuration missing'
            );
        } else {
            $this->fail('.env.production file not found');
        }
    }

    private function verifySecuritySettings(): void
    {
        $this->section('2. Security Settings');

        $envProductionPath = __DIR__.'/.env.production';

        if (file_exists($envProductionPath)) {
            $content = file_get_contents($envProductionPath);

            $this->check(
                str_contains($content, 'APP_KEY='),
                'APP_KEY placeholder exists',
                'APP_KEY not configured'
            );

            $this->check(
                str_contains($content, 'SESSION_DOMAIN='),
                'SESSION_DOMAIN configured',
                'SESSION_DOMAIN not configured'
            );

            $this->check(
                str_contains($content, 'SANCTUM_STATEFUL_DOMAINS='),
                'SANCTUM_STATEFUL_DOMAINS configured',
                'SANCTUM_STATEFUL_DOMAINS not configured'
            );

            $this->check(
                str_contains($content, 'SESSION_ENCRYPT=true'),
                'Session encryption enabled',
                'Session encryption not enabled'
            );

            $this->check(
                str_contains($content, 'SESSION_SECURE_COOKIE=true'),
                'Secure cookies enabled',
                'Secure cookies not enabled'
            );
        } else {
            $this->fail('Cannot verify security settings - .env.production not found');
        }
    }

    private function verifyHTTPSForcing(): void
    {
        $this->section('3. HTTPS Forcing in AppServiceProvider');

        $appServiceProviderPath = __DIR__.'/app/Providers/AppServiceProvider.php';

        if (file_exists($appServiceProviderPath)) {
            $content = file_get_contents($appServiceProviderPath);

            $this->check(
                str_contains($content, "URL::forceScheme('https')") &&
                str_contains($content, "environment('production')"),
                'HTTPS forced in production environment',
                'HTTPS forcing not configured properly'
            );
        } else {
            $this->fail('AppServiceProvider.php not found');
        }
    }

    private function verifyQueueConfiguration(): void
    {
        $this->section('4. Queue Configuration');

        $envProductionPath = __DIR__.'/.env.production';

        if (file_exists($envProductionPath)) {
            $content = file_get_contents($envProductionPath);

            $this->check(
                str_contains($content, 'QUEUE_CONNECTION=redis'),
                'Queue connection set to Redis',
                'Queue connection not set to Redis'
            );

            $this->check(
                str_contains($content, 'REDIS_HOST=') && str_contains($content, 'REDIS_PORT='),
                'Redis configuration present',
                'Redis configuration incomplete'
            );
        } else {
            $this->fail('Cannot verify queue configuration - .env.production not found');
        }
    }

    private function verifySupervisorConfig(): void
    {
        $this->section('5. Supervisor Configuration for Queue Workers');

        $supervisorConfigs = [
            'supervisor-queue-worker.conf',
            'supervisor-laravel-worker.conf',
        ];

        $found = false;
        foreach ($supervisorConfigs as $config) {
            $path = __DIR__.'/'.$config;
            if (file_exists($path)) {
                $this->pass("Supervisor config found: {$config}");
                $found = true;

                $content = file_get_contents($path);

                $this->check(
                    str_contains($content, 'queue:work'),
                    'Queue worker command configured',
                    'Queue worker command not found'
                );

                $this->check(
                    str_contains($content, 'autostart=true') && str_contains($content, 'autorestart=true'),
                    'Auto-start and auto-restart enabled',
                    'Auto-start/restart not properly configured'
                );
            }
        }

        if (! $found) {
            $this->fail('No supervisor configuration file found');
        }
    }

    private function verifyCronConfiguration(): void
    {
        $this->section('6. Cron Configuration for Task Scheduler');

        $cronExample = __DIR__.'/crontab-example.txt';

        if (file_exists($cronExample)) {
            $this->pass('Cron example file exists');

            $content = file_get_contents($cronExample);

            $this->check(
                str_contains($content, 'schedule:run'),
                'Schedule:run command in cron example',
                'Schedule:run command not found in cron example'
            );

            $this->check(
                str_contains($content, '* * * * *'),
                'Cron timing configured correctly (every minute)',
                'Cron timing not configured correctly'
            );
        } else {
            $this->fail('Cron example file not found');
        }
    }

    private function verifyScheduledTasks(): void
    {
        $this->section('7. Scheduled Tasks in Console');

        $consolePath = __DIR__.'/routes/console.php';

        if (file_exists($consolePath)) {
            $content = file_get_contents($consolePath);

            $this->check(
                str_contains($content, 'backup:run'),
                'Daily database backup scheduled',
                'Daily database backup not scheduled'
            );

            $this->check(
                str_contains($content, 'reports:daily-sales-summary') ||
                str_contains($content, 'DailySalesSummary'),
                'Daily sales summary email scheduled',
                'Daily sales summary email not scheduled'
            );

            $this->check(
                str_contains($content, 'Schedule::'),
                'Task scheduling properly configured',
                'Task scheduling not found'
            );

            // Check for DailySalesSummary command
            $commandPath = __DIR__.'/app/Console/Commands/DailySalesSummary.php';
            if (file_exists($commandPath)) {
                $this->pass('DailySalesSummary command exists');

                // Check for email view
                $emailViewPath = __DIR__.'/resources/views/emails/daily-sales-summary.blade.php';
                $this->check(
                    file_exists($emailViewPath),
                    'Daily sales summary email view exists',
                    'Daily sales summary email view missing'
                );
            } else {
                $this->warn('DailySalesSummary command not found');
            }
        } else {
            $this->fail('routes/console.php not found');
        }
    }

    private function verifyStorageLink(): void
    {
        $this->section('8. Storage Link Configuration');

        $publicStoragePath = __DIR__.'/public/storage';

        if (file_exists($publicStoragePath) || is_link($publicStoragePath)) {
            $this->pass('Storage link exists (or can be created with: php artisan storage:link)');
        } else {
            $this->warn('Storage link not created - run: php artisan storage:link');
        }
    }

    private function verifyFilePermissions(): void
    {
        $this->section('9. File Permissions');

        $storagePath = __DIR__.'/storage';
        $bootstrapCachePath = __DIR__.'/bootstrap/cache';

        if (is_writable($storagePath)) {
            $this->pass('Storage directory is writable');
        } else {
            $this->fail('Storage directory is NOT writable - fix with: chmod -R 775 storage');
        }

        if (is_writable($bootstrapCachePath)) {
            $this->pass('Bootstrap/cache directory is writable');
        } else {
            $this->fail('Bootstrap/cache directory is NOT writable - fix with: chmod -R 775 bootstrap/cache');
        }
    }

    private function verifyLogging(): void
    {
        $this->section('10. Logging Configuration');

        $loggingConfigPath = __DIR__.'/config/logging.php';

        if (file_exists($loggingConfigPath)) {
            $content = file_get_contents($loggingConfigPath);

            $this->check(
                str_contains($content, "'stack'"),
                'Stack log channel configured',
                'Stack log channel not found'
            );

            $this->check(
                str_contains($content, "'slack'"),
                'Slack log channel configured',
                'Slack log channel not configured'
            );

            $this->check(
                str_contains($content, "'mail'") || str_contains($content, 'NativeMailerHandler'),
                'Email log channel configured for critical errors',
                'Email logging not configured'
            );

            $this->check(
                str_contains($content, "'critical'"),
                'Critical log channel configured',
                'Critical log channel not found'
            );
        } else {
            $this->fail('config/logging.php not found');
        }

        // Check .env.production for logging settings
        $envProductionPath = __DIR__.'/.env.production';
        if (file_exists($envProductionPath)) {
            $content = file_get_contents($envProductionPath);

            $this->check(
                str_contains($content, 'LOG_CHANNEL=stack'),
                'LOG_CHANNEL set to stack in .env.production',
                'LOG_CHANNEL not configured properly'
            );

            $this->check(
                str_contains($content, 'LOG_SLACK_WEBHOOK_URL='),
                'Slack webhook URL placeholder configured',
                'Slack webhook URL not configured'
            );

            $this->check(
                str_contains($content, 'MAIL_ERROR_TO='),
                'Error notification email configured',
                'Error notification email not configured'
            );
        }
    }

    private function verifyReverbConfiguration(): void
    {
        $this->section('11. Reverb Production Configuration');

        $envProductionPath = __DIR__.'/.env.production';

        if (file_exists($envProductionPath)) {
            $content = file_get_contents($envProductionPath);

            $this->check(
                str_contains($content, 'REVERB_HOST=') && ! str_contains($content, 'REVERB_HOST=localhost'),
                'REVERB_HOST configured for production',
                'REVERB_HOST not configured for production domain'
            );

            $this->check(
                str_contains($content, 'REVERB_PORT=443') || str_contains($content, 'REVERB_PORT=8080'),
                'REVERB_PORT configured',
                'REVERB_PORT not configured'
            );

            $this->check(
                str_contains($content, 'REVERB_SCHEME=https') || str_contains($content, 'wss://'),
                'Reverb configured for secure WebSocket (wss://)',
                'Reverb not configured for secure WebSocket'
            );

            $this->check(
                str_contains($content, 'REVERB_SERVER_HOST=') && str_contains($content, 'REVERB_SERVER_PORT='),
                'Reverb server binding configured',
                'Reverb server binding not configured'
            );
        }

        // Check for supervisor config
        $reverbSupervisor = __DIR__.'/supervisor-reverb.conf';
        if (file_exists($reverbSupervisor)) {
            $this->pass('Reverb supervisor configuration exists');
        } else {
            $this->warn('Reverb supervisor configuration not found');
        }
    }

    private function verifyBackupConfiguration(): void
    {
        $this->section('12. Database Backup Configuration');

        // Check if spatie/laravel-backup is installed
        $composerPath = __DIR__.'/composer.json';
        if (file_exists($composerPath)) {
            $content = file_get_contents($composerPath);

            $this->check(
                str_contains($content, 'spatie/laravel-backup'),
                'Laravel Backup package (spatie/laravel-backup) installed',
                'Laravel Backup package not installed'
            );
        }

        // Check if backup is scheduled
        $consolePath = __DIR__.'/routes/console.php';
        if (file_exists($consolePath)) {
            $content = file_get_contents($consolePath);

            $this->check(
                str_contains($content, 'backup:run'),
                'Database backup scheduled in console.php',
                'Database backup not scheduled'
            );

            $this->check(
                str_contains($content, 'backup:clean'),
                'Old backup cleanup scheduled',
                'Old backup cleanup not scheduled'
            );
        }

        // Check .env.production for backup settings
        $envProductionPath = __DIR__.'/.env.production';
        if (file_exists($envProductionPath)) {
            $content = file_get_contents($envProductionPath);

            if (str_contains($content, 'BACKUP_')) {
                $this->pass('Backup environment variables configured');
            } else {
                $this->warn('Backup environment variables not found (optional)');
            }
        }
    }

    private function verifyDocumentation(): void
    {
        $this->section('13. Documentation (DEPLOYMENT.md)');

        $deploymentMdPath = __DIR__.'/DEPLOYMENT.md';

        if (file_exists($deploymentMdPath)) {
            $this->pass('DEPLOYMENT.md exists');

            $content = file_get_contents($deploymentMdPath);
            $size = strlen($content);

            if ($size > 5000) {
                $this->pass('DEPLOYMENT.md is comprehensive ('.number_format($size).' bytes)');
            } else {
                $this->warn('DEPLOYMENT.md exists but may be incomplete');
            }

            // Check for key sections
            $requiredSections = [
                'Environment Configuration' => false,
                'Queue Workers' => false,
                'Task Scheduling' => false,
                'Database Backup' => false,
                'SSL' => false,
                'File Permissions' => false,
            ];

            foreach ($requiredSections as $section => $found) {
                if (stripos($content, $section) !== false) {
                    $requiredSections[$section] = true;
                }
            }

            $foundSections = array_filter($requiredSections);
            $totalSections = count($requiredSections);
            $foundCount = count($foundSections);

            $this->check(
                $foundCount >= ($totalSections - 1),
                "Documentation contains required sections ({$foundCount}/{$totalSections})",
                'Documentation missing some required sections'
            );
        } else {
            $this->fail('DEPLOYMENT.md not found');
        }
    }

    private function section(string $title): void
    {
        echo "\n".str_repeat('-', 80)."\n";
        echo $title."\n";
        echo str_repeat('-', 80)."\n";
    }

    private function check(bool $condition, string $passMessage, string $failMessage): void
    {
        if ($condition) {
            $this->pass($passMessage);
        } else {
            $this->fail($failMessage);
        }
    }

    private function pass(string $message): void
    {
        $this->passed++;
        echo "✓ PASS: {$message}\n";
    }

    private function fail(string $message): void
    {
        $this->failed++;
        echo "✗ FAIL: {$message}\n";
    }

    private function warn(string $message): void
    {
        $this->warnings++;
        echo "⚠ WARN: {$message}\n";
    }

    private function printSummary(): void
    {
        echo "\n".str_repeat('=', 80)."\n";
        echo "VERIFICATION SUMMARY\n";
        echo str_repeat('=', 80)."\n";
        echo "Passed:   {$this->passed}\n";
        echo "Failed:   {$this->failed}\n";
        echo "Warnings: {$this->warnings}\n";
        echo str_repeat('=', 80)."\n";

        if ($this->failed > 0) {
            echo "\n❌ Some checks failed. Please review the failures above.\n";
            exit(1);
        } elseif ($this->warnings > 0) {
            echo "\n⚠️  All critical checks passed, but there are warnings to review.\n";
            exit(0);
        } else {
            echo "\n✅ All verification checks passed!\n";
            echo "\n📋 Next Steps:\n";
            echo "   1. Copy .env.production to .env on your production server\n";
            echo "   2. Run: php artisan key:generate\n";
            echo "   3. Update all placeholder values in .env (database, Redis, domains, API keys)\n";
            echo "   4. Copy supervisor configs to /etc/supervisor/conf.d/\n";
            echo "   5. Add cron entry from crontab-example.txt\n";
            echo "   6. Run: php artisan storage:link\n";
            echo "   7. Set proper file permissions (see DEPLOYMENT.md)\n";
            echo "   8. Review DEPLOYMENT.md for complete deployment instructions\n";
            exit(0);
        }
    }
}

// Run verification
$verifier = new ProductionSetupVerifier;
$verifier->run();
