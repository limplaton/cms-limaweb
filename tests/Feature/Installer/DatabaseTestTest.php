<?php

namespace Tests\Feature\Installer;

use App\Installer\DatabaseTest;
use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class DatabaseTestTest extends TestCase
{
    protected $mockConnection;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock the database connection
        $this->mockConnection = $this->partialMock(Connection::class);
    }

    public function test_it_tests_create_table()
    {
        Schema::shouldReceive('setConnection')->andReturnSelf();

        $databaseTest = new DatabaseTest($this->mockConnection);

        Schema::shouldReceive('create')
            ->with('test_table', Mockery::any())
            ->andReturn(true);

        Schema::shouldReceive('dropIfExists')
            ->andReturn(true);

        $this->expectNotToPerformAssertions();

        $databaseTest->testCreateTable();
    }

    public function test_it_tests_drop_table()
    {
        Schema::shouldReceive('setConnection')->andReturnSelf();

        $databaseTest = new DatabaseTest($this->mockConnection);

        Schema::shouldReceive('dropIfExists')
            ->andReturn(true);

        $this->expectNotToPerformAssertions();

        $databaseTest->testDropTable();
    }

    public function test_it_can_fail_on_test_drop_table()
    {
        Schema::shouldReceive('setConnection')->andReturnSelf();

        $databaseTest = new DatabaseTest($this->mockConnection);

        Schema::shouldReceive('dropIfExists')
            ->andThrow($this->newQueryException());

        $databaseTest->testDropTable();
        $this->assertNotEmpty($databaseTest->getLastError());
    }

    public function test_it_tests_insert()
    {
        $this->schemaCreateBaseAssertions();

        $databaseTest = new DatabaseTest($this->mockConnection);

        DB::shouldReceive('insert')
            ->once()
            ->andReturn(true);

        $databaseTest->testInsert();
    }

    public function test_it_tests_select()
    {
        $this->schemaCreateBaseAssertions();

        $databaseTest = new DatabaseTest($this->mockConnection);

        DB::shouldReceive('select')
            ->once()
            ->andReturn([]);

        $databaseTest->testSelect();
    }

    public function test_it_tests_update()
    {
        $this->schemaCreateBaseAssertions();

        $databaseTest = new DatabaseTest($this->mockConnection);

        DB::shouldReceive('insert')
            ->once()
            ->andReturn(true);

        DB::shouldReceive('table')
            ->once()
            ->andReturnSelf();

        DB::shouldReceive('update')
            ->once()
            ->andReturn(1);

        $databaseTest->testUpdate();
    }

    public function test_it_tests_delete()
    {
        $this->schemaCreateBaseAssertions();

        $databaseTest = new DatabaseTest($this->mockConnection);

        DB::shouldReceive('insert')
            ->once()
            ->andReturn(true);

        DB::shouldReceive('table')
            ->once()
            ->andReturnSelf();

        DB::shouldReceive('delete')
            ->once()
            ->andReturn(1);

        $databaseTest->testDelete();
    }

    public function test_it_tests_alter()
    {
        Schema::shouldReceive('setConnection')->andReturnSelf();

        $databaseTest = new DatabaseTest($this->mockConnection);

        Schema::shouldReceive('create')
            ->once()
            ->withArgs(['test_table', Mockery::any()])
            ->andReturnUsing(function ($table, $callback) {
                $mock = $this->partialMock(Blueprint::class, function (MockInterface $blueprintMock) {
                    $blueprintMock->shouldReceive('primary')
                        ->with('id')
                        ->once()
                        ->andReturnSelf();
                });

                $callback($mock);
            });

        Schema::shouldReceive('dropIfExists')
            ->once()
            ->andReturn(true);

        $databaseTest->testAlter();
    }

    public function test_it_tests_index()
    {
        $this->schemaCreateBaseAssertions();

        $databaseTest = new DatabaseTest($this->mockConnection);

        DB::shouldReceive('insert')
            ->once()
            ->andReturn(true);

        $this->mockConnection->shouldReceive('getPdo')
            ->andReturnSelf();

        $this->mockConnection->shouldReceive('exec')
            ->with('CREATE INDEX test_column_index ON test_table (test_column(10))')
            ->once()
            ->andReturn(true);

        $databaseTest->testIndex();
    }

    public function test_it_tests_references()
    {
        $databaseTest = new DatabaseTest($this->mockConnection);

        Schema::shouldReceive('setConnection')->andReturnSelf();

        // Simulate table creation successfully for both tables
        Schema::shouldReceive('create')
            ->with('test_users', Mockery::on(function ($callback) {
                $callback(new Blueprint('test_users'));

                return true;
            }))
            ->once()
            ->andReturnTrue();

        Schema::shouldReceive('create')
            ->with('test_table', Mockery::on(function ($callback) {
                $callback(new Blueprint('test_table'));

                return true;
            }))
            ->once()
            ->andReturnTrue();

        Schema::shouldReceive('dropIfExists')
            ->with('test_table')
            ->once()
            ->andReturnTrue();

        Schema::shouldReceive('dropIfExists')
            ->with('test_users')
            ->once()
            ->andReturnTrue();

        $databaseTest->testReferences();
    }

    public function test_it_can_fail_on_test_references()
    {
        Schema::shouldReceive('setConnection')->andReturnSelf();

        $databaseTest = new DatabaseTest($this->mockConnection);

        // Mock the creation of the first table without throwing exceptions
        Schema::shouldReceive('create')
            ->with('test_users', Mockery::any())
            ->once()
            ->andReturn(true);

        // Mock the creation of the second table which references the first, potentially throwing an exception
        Schema::shouldReceive('create')
            ->with('test_table', Mockery::any())
            ->once()
            ->andThrow($this->newQueryException());

        try {
            $databaseTest->testReferences();
        } catch (\Exception $e) {
            // Catching the exception to verify it later
        }

        // Verifying that an exception was thrown and a last error was set
        $this->assertNotEmpty($databaseTest->getLastError(), 'An error should be set if REFERENCES test fails');
    }

    public function test_it_captures_exception_on_perform_test()
    {
        Schema::shouldReceive('setConnection')->andReturnSelf();

        $databaseTest = new DatabaseTest($this->mockConnection);

        Schema::shouldReceive('create')
            ->with('test_table', Mockery::any())
            ->andThrow($this->newQueryException());

        Schema::shouldReceive('dropIfExists')
            ->andReturn(true);

        $databaseTest->testCreateTable();
        $this->assertNotEmpty($databaseTest->getLastError());
    }

    protected function schemaCreateBaseAssertions()
    {
        Schema::shouldReceive('setConnection')->andReturnSelf();

        DB::shouldReceive('usingConnection')
            ->withAnyArgs()
            ->andReturnUsing(function ($connectionName, $callback) {
                $callback();
            });

        Schema::shouldReceive('create')
            ->once()
            ->andReturn(true);

        Schema::shouldReceive('dropIfExists')
            ->once()
            ->andReturn(true);
    }

    protected function newQueryException(): QueryException
    {
        return new QueryException($this->mockConnection->getName(), '12345 COMMAND command denied', [], new \Exception());
    }
}
