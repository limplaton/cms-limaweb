<?php

/**
 * Detached helper not dependent on any Laravel functions, new PHP versions, classes and namespaces.
 */
class DetachedHelper
{
    const MINIMUM_PHP_VERSION = '8.1';

    const MINIMUM_RECOMMENDED_PHP_MEMORY_LIMIT = 128;

    const MAINTENANCE_FILE_PATH = __DIR__.'/storage/framework/maintenance.php';

    const DOWN_FILE_PATH = __DIR__.'/storage/framework/down';

    const INSTALLED_FILE = '.installed';

    const INSTALL_ROUTE_PREFIX = 'install';

    const BASE_PATH = __DIR__;

    const CORE_MODULES = [
        'Activities', 'Billable', 'Brands', 'Calls', 'Comments', 'Contacts', 'Core', 'Deals',
        'Documents', 'MailClient', 'Notes', 'ThemeStyle', 'Translator', 'Users', 'WebForms',
    ];

    protected static $iniAllData;

    public static function isUsingMinimumPhpVersion()
    {
        return version_compare(phpversion(), static::MINIMUM_PHP_VERSION, '>=');
    }

    // NOTE: Perhaps from v1.1.2 this is no longer needed? The updater is catching the \Throwable exception
    // in previous versions, only "finally" was used, in this case, on fatal error, will disable maintenance mode
    public static function shouldDisableMaintenance()
    {
        if (! file_exists(static::MAINTENANCE_FILE_PATH)) {
            return false;
        }

        // Used to fix the updater for users performing update from version lower than 1.1.0
        // The updater throws an error and the app is stuck in maintenance mode
        // The code will disable maintenance mode after 3 minutes and a page will be shown to finalize the update
        $started = filectime(static::MAINTENANCE_FILE_PATH);

        if (! $started) {
            return false;
        }

        // We will check if the file creation date is older then 3 minutes
        // It should not take more then 3 minutes to perform an update
        if ((time() - $started) / 60 >= 3 && file_exists(static::DOWN_FILE_PATH)) {
            return strpos(
                file_get_contents(static::DOWN_FILE_PATH),
                'Application update is in progress'
            ) !== false;
        }

        return false;
    }

    public static function disableMaintenance()
    {
        unlink(static::MAINTENANCE_FILE_PATH);
        unlink(static::DOWN_FILE_PATH);
    }

    public static function memoryLimitIsTooLow()
    {
        $limit = static::getMemoryLimitInMegabytes();

        if ($limit === -1) {
            return false;
        }

        return $limit < static::MINIMUM_RECOMMENDED_PHP_MEMORY_LIMIT;
    }

    public static function getMemoryLimitInMegabytes()
    {
        $limit = static::memoryLimitToBytes(ini_get('memory_limit'));

        if ($limit === -1) {
            return $limit;
        }

        return $limit / (1024 * 1024);
    }

    /** Use only if autoloader included */
    public static function requiresInstallation()
    {
        return ! file_exists(implode(DIRECTORY_SEPARATOR, [
            static::BASE_PATH,
            'storage',
            static::INSTALLED_FILE,
        ]));
    }

    public static function isInstalling()
    {
        return strpos($_SERVER['REQUEST_URI'], static::INSTALL_ROUTE_PREFIX) !== false;
    }

    public static function hasCoreModulesDisabled()
    {
        $statusesPath = __DIR__.DIRECTORY_SEPARATOR.'modules_statuses.json';

        if (! is_file($statusesPath)) {
            exit('<h1>Missing modules_statuses.json file.</h1>');
        }

        $modules = json_decode(file_get_contents($statusesPath), true);

        foreach (static::CORE_MODULES as $moduleName) {
            if (! isset($modules[$moduleName]) ||
                 $modules[$moduleName] == false ||
                ! is_dir(__DIR__.'/modules/'.$moduleName)) {
                return true;
            }
        }

        return false;
    }

    public static function memoryLimitToBytes($memoryLimit)
    {
        if ($memoryLimit === '-1') {
            return -1;
        }

        $memoryLimit = strtolower($memoryLimit);
        $max = strtolower(ltrim($memoryLimit, '+'));

        if (str_starts_with($max, '0x')) {
            $max = \intval($max, 16);
        } elseif (str_starts_with($max, '0')) {
            $max = \intval($max, 8);
        } else {
            $max = (int) $max;
        }

        switch (substr($memoryLimit, -1)) {
            case 't': $max *= 1024;
                // no break
            case 'g': $max *= 1024;
                // no break
            case 'm': $max *= 1024;
                // no break
            case 'k': $max *= 1024;
        }

        return $max;
    }

    public static function raiseMemoryLimit($limit)
    {
        // Return early if the limit cannot be changed.
        if (static::isIniValueChangeable('memory_limit') === false) {
            return false;
        }

        $currentLimitInBytes = static::memoryLimitToBytes(ini_get('memory_limit'));

        if ($currentLimitInBytes === -1) {
            return false;
        }

        $limitInBytes = static::memoryLimitToBytes($limit);

        if ($limitInBytes === -1 || $limitInBytes > $currentLimitInBytes) {
            if (ini_set('memory_limit', $limit) !== false) {
                return $limit;
            }

            return false;
        }

        return false;
    }

    public static function isIniValueChangeable($setting)
    {
        if (! isset(static::$iniAllData)) {
            static::$iniAllData = false;
            // Sometimes `ini_get_all()` is disabled via the `disable_functions` option for "security purposes".
            if (function_exists('ini_get_all')) {
                static::$iniAllData = ini_get_all();
            }
        }

        if (isset(static::$iniAllData[$setting]['access']) &&
        (INI_ALL === (static::$iniAllData[$setting]['access'] & 7) ||
         INI_USER === (static::$iniAllData[$setting]['access'] & 7))) {
            return true;
        }

        // If we were unable to retrieve the details, fail gracefully to assume it's changeable.
        return ! is_array(static::$iniAllData);
    }
}
