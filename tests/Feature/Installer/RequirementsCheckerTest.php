<?php

namespace Tests\Feature\Installer;

use App\Installer\RequirementsChecker;
use Tests\TestCase;

class RequirementsCheckerTest extends TestCase
{
    protected $requirementsChecker;

    protected function setUp(): void
    {
        parent::setUp();

        $requirements = [
            'php' => ['curl', 'json'],
            'functions' => ['symlink', 'tmpfile'],
            'apache' => ['mod_rewrite'],
            'recommended' => [
                'php' => ['imap', 'zip'],
                'functions' => ['proc_open', 'proc_close'],
            ],
        ];

        $minPhpVersion = '8.1';

        $this->requirementsChecker = $this->getMockBuilder(RequirementsChecker::class)
            ->setConstructorArgs([$requirements, $minPhpVersion])
            ->onlyMethods(['extensionLoaded', 'isSupportedPHPVersion', 'functionExists'])
            ->getMock();
    }

    public function test_it_checks_for_required_php_extensions()
    {
        $this->requirementsChecker
            ->method('extensionLoaded')
            ->willReturnCallback(fn ($ext) => $ext === 'curl' ? true : false);

        $checks = $this->requirementsChecker->check();

        $this->assertTrue($checks['results']['php']['curl']);
        $this->assertFalse($checks['results']['php']['json']);
    }

    public function test_it_checks_for_required_php_functions()
    {
        $this->requirementsChecker
            ->method('functionExists')
            ->willReturnCallback(fn ($fn) => $fn === 'symlink' ? true : false);

        $results = $this->requirementsChecker->check();

        $this->assertTrue($results['results']['functions']['symlink']);
        $this->assertFalse($results['results']['functions']['tmpfile']);
    }

    public function test_it_checks_for_recommended_extensions()
    {
        $this->requirementsChecker
            ->method('extensionLoaded')
            ->willReturnCallback(fn ($ext) => $ext === 'imap' ? true : false);

        $checks = $this->requirementsChecker->check();

        $this->assertTrue($checks['recommended']['php']['imap']);
        $this->assertFalse($checks['recommended']['php']['zip']);
    }

    public function test_it_checks_for_recommended_functions()
    {
        $this->requirementsChecker
            ->method('functionExists')
            ->willReturnCallback(fn ($function) => $function === 'proc_open' ? true : false);

        $checks = $this->requirementsChecker->check();

        $this->assertTrue($checks['recommended']['functions']['proc_open']);
        $this->assertFalse($checks['recommended']['functions']['proc_close']);
    }

    public function test_check_php_version()
    {
        $this->requirementsChecker->method('isSupportedPHPVersion')->willReturn(true);

        $versionCheck = $this->requirementsChecker->checkPHPversion();

        $this->assertTrue($versionCheck['supported']);
    }

    public function test_fails_with_unmet_extension_requirement()
    {
        $this->requirementsChecker->method('extensionLoaded')->willReturn(false);

        $fail = $this->requirementsChecker->fails('curl');

        $this->assertTrue($fail);
    }

    public function test_fails_with_unmet_recommended_extension_requirement()
    {
        $this->requirementsChecker->method('functionExists')->willReturn(false);

        $fail = $this->requirementsChecker->fails('imap');

        $this->assertTrue($fail);
    }
}
