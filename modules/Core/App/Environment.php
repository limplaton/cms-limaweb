<?php
 

namespace Modules\Core\App;

use Illuminate\Support\Facades\DB;

class Environment
{
    /**
     * Capture the installation date.
     */
    public static function setInstallationDate(): void
    {
        settings(['_installed_date' => date('Y-m-d H:i:s')]);
    }

    /**
     * Capture the current environment in storage.
     */
    public static function capture(array $extra = []): void
    {
        settings(array_merge([
            '_env_captured_at' => now()->toISOString(), // mostly used for tests
            '_app_url' => config('app.url'),
            '_prev_app_url' => settings('_app_url'),
            '_server_ip' => $_SERVER['SERVER_ADDR'] ?? '', // may not be always reliable
            '_server_hostname' => gethostname() ?: '',
            '_db_driver_version' => DB::connection()->getPdo()->getAttribute(\PDO::ATTR_SERVER_VERSION),
            '_db_driver' => DB::connection()->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME),
            '_php_version' => PHP_VERSION,
            '_version' => \Modules\Core\App\Application::VERSION,
        ], $extra));
    }

    /**
     * Capture the cron job environment.
     */
    public static function captureCron(): void
    {
        settings()->set([
            '_last_cron_run' => now(),
            '_cron_job_last_user' => get_current_process_user(),
            '_cron_php_version' => PHP_VERSION,
        ])->save();
    }

    /**
     * Determine whether critical environment values are changed.
     */
    public static function hasChanged(): bool
    {
        return rtrim(config('app.url'), '/') != rtrim(settings('_app_url'), '/') ||
            (! empty(settings('_php_version')) && settings('_php_version') != PHP_VERSION) ||
            (! empty(settings('_server_hostname')) && settings('_server_hostname') != gethostname());
    }
}
