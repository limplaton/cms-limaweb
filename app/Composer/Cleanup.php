<?php
 

namespace App\Composer;

use Composer\IO\IOInterface;
use Composer\Script\Event;
use Composer\Util\Filesystem;

/**
 * @codeCoverageIgnore
 */
class Cleanup
{
    /**
     * @param  Event  $event  Composer event passed in for any script method
     */
    public static function perform(Event $event)
    {
        $startTime = \microtime(true);

        $savedSizeBytes = static::cleanUpAwsServices($event);

        $savedSizeBytes += static::cleanUpGoogleServices($event);

        static::cleanupAllPackages($event, $startTime, $savedSizeBytes);
    }

    /**
     * @see https://github.com/googleapis/google-api-php-client#cleaning-up-unused-services
     */
    protected static function cleanUpGoogleServices(Event $event): int
    {
        $vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');
        $serviceDir = "$vendorDir/google/apiclient-services/src";

        if (! is_dir($serviceDir)) {
            return 0;
        }

        $fs = new Filesystem;

        $sizeBeforeCleanup = $fs->size($serviceDir);

        \Google\Task\Composer::cleanup($event);

        $sizeAfterCleanup = $fs->size($serviceDir);

        return $sizeBeforeCleanup - $sizeAfterCleanup;
    }

    /**
     * @see https://github.com/googleapis/google-api-php-client#cleaning-up-unused-services
     */
    protected static function cleanUpAwsServices(Event $event): int
    {
        $vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');
        $serviceDir = "$vendorDir/aws/aws-sdk-php/src";

        if (! is_dir($serviceDir)) {
            return 0;
        }

        $fs = new Filesystem;

        $sizeBeforeCleanup = $fs->size($serviceDir);

        \Aws\Script\Composer\Composer::removeUnusedServices($event);

        $sizeAfterCleanup = $fs->size($serviceDir);

        return $sizeBeforeCleanup - $sizeAfterCleanup;
    }

    protected static function cleanupAllPackages(Event $event, float $startTime, int $savedSizeBytes): void
    {
        $io = $event->getIO();
        $composer = $event->getComposer();

        $fs = new Filesystem;

        $globalRules = static::getGlobalRules();
        $packageRules = static::getPackageRules();

        $installationManager = $composer->getInstallationManager();

        // Loop over all installed packages
        foreach ($composer->getRepositoryManager()->getLocalRepository()->getPackages() as $package) {
            $packageName = $package->getName();
            $installPath = $installationManager->getInstallPath($package);

            $savedSizeBytes += self::makeClean($installPath, $globalRules, $fs, $io);

            // Try to extract defined targets for a package
            if (isset($packageRules[$packageName])) {
                $savedSizeBytes += self::makeClean($installPath, $packageRules[$packageName], $fs, $io);
            }
        }

        $io->write(\sprintf(
            '<info>Cleanup:</info> Cleanup done in %01.3f seconds (<comment>%s</comment> saved)',
            \microtime(true) - $startTime,
            static::formatBytes($savedSizeBytes)
        ));
    }

    /**
     * @param  array<string>  $rules
     */
    protected static function makeClean(string $packagePath, array $rules, Filesystem $fs, IOInterface $io): int
    {
        $savedSizeBytes = 0;

        foreach ($rules as $rule) {
            $paths = \glob($packagePath.DIRECTORY_SEPARATOR.\ltrim(\trim($rule), '\\/'), \GLOB_ERR);

            if (\is_array($paths)) {
                foreach ($paths as $path) {
                    try {
                        $pathSize = $fs->size($path);

                        if ($fs->remove($path)) {
                            $savedSizeBytes += $pathSize;
                        }
                    } catch (\Throwable $e) {
                        $io->writeError(\sprintf(
                            '<info>Cleanup</info> Error occurred: %s', $e->getMessage()
                        ));
                    }
                }
            }
        }

        return $savedSizeBytes;
    }

    /**
     * Get global packages cleanup rules.
     *
     * Values can contains asterisk (`*` - zero or more characters) and question mark (`?` - exactly one character).
     *
     * @see <https://www.php.net/manual/en/function.glob.php#refsect1-function.glob-parameters>
     *
     * @return array<string>
     */
    protected static function getGlobalRules(): array
    {
        return [
            '*.md', '*.MD', '*.rst', '*.RST', '*.markdown',
            // Markdown/reStructuredText files like `README.md`, `changelog.MD`..
            'AUTHORS', 'LICENSE', 'COPYING', 'AUTHORS', // Text files without extensions
            'CHANGES.txt', 'CHANGES', 'CHANGELOG.txt', 'LICENSE.txt', 'TODO.txt', 'README.txt', // Text files
            '.github', '.gitlab', // .git* specific directories
            '.gitignore', '.gitattributes', // git-specific files
            'phpunit.xml*', 'phpstan.neon*', 'phpbench.*', 'psalm.*', '.psalm', // Test configurations
            '.travis.yml', '.travis', '.scrutinizer.yml', '.circleci', 'appveyor.yml', // CI
            '.codecov.yml', '.coveralls.yml', '.styleci.yml', '.dependabot', // CI
            '.php_cs', '.php_cs.*', 'phpcs.*', '.*lint', // Code-style definitions
            '.gush.yml', 'bors.toml', '.pullapprove.yml', // 3rd party integrations
            '.editorconfig', '.idea', '.vscode', // Configuration for editors
            'phive.xml', 'build.xml', // Build configurations
            'composer.lock', // Composer lock file
            'Makefile', // Scripts, Makefile
            'Dockerfile', 'docker-compose.yml', 'docker-compose.yaml', '.dockerignore', // Docker
        ];
    }

    /**
     * Get packages cleanup rules as array, where key is package name, and value is an array of directories and/or
     * file names, which must be deleted.
     *
     * Values can contains asterisk (`*` - zero or more characters) and question mark (`?` - exactly one character).
     *
     * @see <https://www.php.net/manual/en/function.glob.php#refsect1-function.glob-parameters>
     *
     * @return array<string, array<string>>
     */
    protected static function getPackageRules(): array
    {
        return [
            // App specific
            'microsoft/microsoft-graph' => ['src/Beta'],

            // General
            'monolog/monolog' => ['tests'],
            'myclabs/deep-copy' => ['doc'],
            'nikic/php-parser' => ['test', 'test_old', 'doc'],
            'phpstan/phpdoc-parser' => ['doc'],
            'spatie/laravel-permission' => ['art', 'docs'],
            'symfony/css-selector' => ['Tests'],
            'symfony/debug' => ['Tests'],
            'symfony/event-dispatcher' => ['Tests'],
            'symfony/filesystem' => ['Tests'],
            'symfony/finder' => ['Tests'],
            'symfony/http-foundation' => ['Tests'],
            'symfony/http-kernel' => ['Tests'],
            'symfony/options-resolver' => ['Tests'],
            'symfony/routing' => ['Tests'],
            'symfony/stopwatch' => ['Tests'],
            'symfony/console' => ['Tester'],

            'google/apiclient' => ['docs'],
            'phenx/php-font-lib' => ['tests'],
            'predis/predis' => ['examples'],

            'elasticsearch/elasticsearch' => ['tests', 'travis', 'docs'],
            'sabberworm/php-css-parser' => ['tests'],

            'dragonmantank/cron-expression' => ['tests'],
            'erusev/parsedown-extra' => ['test'],
            'friendsofphp/php-cs-fixer' => ['*.sh', 'doc'], // Note: `tests` must be not included
            'fakerphp/faker' => \array_merge(self::getFakerPhpRules(), ['test']),
            'hamcrest/hamcrest-php' => ['tests'],
            'mockery/mockery' => ['tests', 'docker', 'docs'],
            'mtdowling/jmespath.php' => ['tests'],
            'paragonie/random_compat' => ['other', '*.sh'],
            'paragonie/sodium_compat' => ['*.sh', 'plasm-*.*', 'dist'],
            'phar-io/manifest' => ['tests', 'examples'],
            'phar-io/version' => ['tests'],
            'phpunit/php-code-coverage' => ['tests'],
            'phpunit/php-file-iterator' => ['tests'],
            'phpunit/php-timer' => ['tests'],
            'phpunit/php-token-stream' => ['tests'],
            'phpunit/phpunit' => ['tests'],
            'phpunit/php-invoker' => ['tests'],
            'phpunit/phpunit-selenium' => ['Tests', 'selenium-1-tests'],
            'psy/psysh' => ['.phan', 'test', 'vendor-bin'],

            'sebastian/code-unit-reverse-lookup' => ['tests'],
            'sebastian/comparator' => ['tests'],
            'sebastian/diff' => ['tests'],
            'sebastian/environment' => ['tests'],
            'sebastian/exporter' => ['tests'],
            'sebastian/object-enumerator' => ['tests'],
            'sebastian/object-reflector' => ['tests'],
            'sebastian/recursion-context' => ['tests'],
            'sebastian/resource-operations' => ['tests', 'build'],
            'sebastian/type' => ['tests'],
            'sebastian/global-state' => ['tests'],
            'sebastian/code-unit' => ['tests'],
            'symfony/psr-http-message-bridge' => ['Tests'],
            'symfony/service-contracts' => ['Test'],
            'symfony/translation' => ['Tests'],
            'symfony/translation-contracts' => ['Test'],
            'symfony/var-dumper' => ['Tests', 'Test'],
            'theseer/tokenizer' => ['tests'],

            'facade/ignition-contracts' => ['Tests', 'docs'],
            'doctrine/annotations' => ['docs'],
            'doctrine/inflector' => ['docs'],
            'doctrine/instantiator' => ['docs'],

            'voku/portable-ascii' => ['docs'],
            'dompdf/dompdf' => ['LICENSE.LGPL'],
            'phenx/php-svg-lib' => ['tests', 'COPYING.GPL'],
            'aws/aws-sdk-php' => ['.changes', '.github'],

            'laravel/ui' => ['tests'],
            'maennchen/zipstream-php' => ['test'],
            'markbaker/matrix' => ['examples'],
            'markbaker/complex' => ['examples'],
            'psr/log' => ['Psr/Log/Test'],
        ];
    }

    /**
     * Package fzaninotto/faker moved to fakerphp/faker.
     *
     * @return array<string>
     */
    protected static function getFakerPhpRules(): array
    {
        return \array_map(static function (string $locale): string {
            return "src/Faker/Provider/{$locale}";
        }, [
            'el_GR', 'en_SG', 'fa_IR', 'ja_JP', 'mn_MN', 'pl_PL', 'vi_VN', 'zh_CN', 'sk_SK',
            'ar_JO', 'en_AU', 'en_UG', 'fi_FI', 'hu_HU', 'ka_GE', 'ms_MY', 'pt_BR', 'sr_RS',
            'ar_SA', 'cs_CZ', 'en_CA', 'hy_AM', 'kk_KZ', 'nb_NO', 'pt_PT', 'sv_SE', 'zh_TW',
            'at_AT', 'da_DK', 'en_ZA', 'fr_BE', 'id_ID', 'ko_KR', 'ne_NP', 'ro_MD', 'tr_TR',
            'en_HK', 'es_AR', 'fr_CA', 'nl_BE', 'ro_RO', 'th_TH', 'fr_CH', 'lt_LT', 'nl_NL',
            'de_AT', 'en_IN', 'es_ES', 'es_PE', 'fr_FR', 'is_IS', 'lv_LV', 'de_CH', 'en_NG',
            'bg_BG', 'de_DE', 'en_NZ', 'es_VE', 'he_IL', 'it_CH', 'me_ME', 'sl_SI', 'bn_BD',
            'el_CY', 'en_PH', 'et_EE', 'hr_HR', 'it_IT', 'uk_UA', 'sr_Cyrl_RS', 'sr_Latn_RS',
        ]);
    }

    /**
     * Format the given bytes in a proper human readable format.
     */
    protected static function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = \max($bytes, 0);
        $pow = \floor(($bytes ? \log($bytes) : 0) / \log(1024));
        $pow = \min($pow, \count($units) - 1);

        $bytes /= \pow(1024, $pow);

        return \round($bytes, $precision).' '.$units[$pow];
    }
}
