<?php

namespace Tests\Feature\Installer;

use App\Installer\DatabaseTest;
use App\Installer\PrivilegeNotGrantedException;
use App\Installer\PrivilegesChecker;
use Illuminate\Database\Connection;
use Mockery;
use Tests\TestCase;

class PrivilegesCheckerTest extends TestCase
{
    protected array $testerMethods;

    protected function setUp(): void
    {
        $this->testerMethods = PrivilegesChecker::getTesterMethods();

        parent::setUp();
    }

    public function test_it_passes_privilege_checks()
    {
        $connection = $this->partialMock(Connection::class);
        $databaseTestMock = Mockery::mock(DatabaseTest::class, [$connection])->makePartial();

        foreach ($this->testerMethods as $method) {
            $databaseTestMock->shouldReceive($method)
                ->once()
                ->andReturn(true);
        }

        $privilegesChecker = new PrivilegesChecker($databaseTestMock);

        $this->expectNotToPerformAssertions();

        $privilegesChecker->check(); // No exception means it passes
    }

    public function test_it_fails_on_any_missing_privileges()
    {
        $this->expectException(PrivilegeNotGrantedException::class);

        $connection = $this->partialMock(Connection::class);
        $databaseTestMock = Mockery::mock(DatabaseTest::class, [$connection])->makePartial();

        foreach ($this->testerMethods as $method) {
            $databaseTestMock->shouldReceive($method)
                ->once()
                ->andThrow(new PrivilegeNotGrantedException('12345 SELECT command denied'));

            $privilegesChecker = new PrivilegesChecker($databaseTestMock);

            $privilegesChecker->check();
        }
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
