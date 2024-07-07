<?php
 

namespace Modules\Core\Tests\Feature;

use Carbon\Carbon;
use Modules\Core\App\Support\ProvidesBetweenArgumentsViaString;
use Tests\TestCase;

class ProvidesBetweenArgumentsViaStringTest extends TestCase
{
    public function test_arguments_via_string()
    {
        $instance = new DummyClass;
        Carbon::setTestNow('2022-01-01');

        $this->assertEquals(
            ['2022-01-01 00:00:00', '2022-01-01 23:59:59'],
            $this->simplify($instance->getBetweenArguments('today'))
        );

        $this->assertEquals(
            ['2021-12-31 00:00:00', '2021-12-31 23:59:59'],
            $this->simplify($instance->getBetweenArguments('yesterday'))
        );

        $this->assertEquals(
            ['2022-01-02 00:00:00', '2022-01-02 23:59:59'],
            $this->simplify($instance->getBetweenArguments('next_day'))
        );

        $this->assertEquals(
            ['2021-12-27 00:00:00', '2022-01-02 23:59:59'],
            $this->simplify($instance->getBetweenArguments('this_week'))
        );

        $this->assertEquals(
            ['2021-12-20 00:00:00', '2021-12-26 23:59:59'],
            $this->simplify($instance->getBetweenArguments('last_week'))
        );

        $this->assertEquals(
            ['2022-01-03 00:00:00', '2022-01-09 23:59:59'],
            $this->simplify($instance->getBetweenArguments('next_week'))
        );

        $this->assertEquals(
            ['2022-01-01 00:00:00', '2022-01-31 23:59:59'],
            $this->simplify($instance->getBetweenArguments('this_month'))
        );

        $this->assertEquals(
            ['2021-12-01 00:00:00', '2021-12-31 23:59:59'],
            $this->simplify($instance->getBetweenArguments('last_month'))
        );

        $this->assertEquals(
            ['2022-02-01 00:00:00', '2022-02-28 23:59:59'],
            $this->simplify($instance->getBetweenArguments('next_month'))
        );

        $this->assertEquals(
            ['2022-01-01 00:00:00', '2022-03-31 23:59:59'],
            $this->simplify($instance->getBetweenArguments('this_quarter'))
        );

        $this->assertEquals(
            ['2021-10-01 00:00:00', '2021-12-31 23:59:59'],
            $this->simplify($instance->getBetweenArguments('last_quarter'))
        );

        $this->assertEquals(
            ['2022-04-01 00:00:00', '2022-06-30 23:59:59'],
            $this->simplify($instance->getBetweenArguments('next_quarter'))
        );

        $this->assertEquals(
            ['2022-01-01 00:00:00', '2022-12-31 23:59:59'],
            $this->simplify($instance->getBetweenArguments('this_year'))
        );

        $this->assertEquals(
            ['2021-01-01 00:00:00', '2021-12-31 23:59:59'],
            $this->simplify($instance->getBetweenArguments('last_year'))
        );

        $this->assertEquals(
            ['2023-01-01 00:00:00', '2023-12-31 23:59:59'],
            $this->simplify($instance->getBetweenArguments('next_year'))
        );

        $this->assertEquals(
            ['2021-12-25 00:00:00', '2022-01-01 23:59:59'],
            $this->simplify($instance->getBetweenArguments('last_7_days'))
        );

        $this->assertEquals(
            ['2021-12-18 00:00:00', '2022-01-01 23:59:59'],
            $this->simplify($instance->getBetweenArguments('last_14_days'))
        );

        $this->assertEquals(
            ['2021-12-02 00:00:00', '2022-01-01 23:59:59'],
            $this->simplify($instance->getBetweenArguments('last_30_days'))
        );

        $this->assertEquals(
            ['2021-11-02 00:00:00', '2022-01-01 23:59:59'],
            $this->simplify($instance->getBetweenArguments('last_60_days'))
        );

        $this->assertEquals(
            ['2021-10-03 00:00:00', '2022-01-01 23:59:59'],
            $this->simplify($instance->getBetweenArguments('last_90_days'))
        );

        $this->assertEquals(
            ['2021-01-01 00:00:00', '2022-01-01 23:59:59'],
            $this->simplify($instance->getBetweenArguments('last_365_days'))
        );

        $this->expectException(\InvalidArgumentException::class);
        $instance->getBetweenArguments('dummy');
    }

    protected function simplify($arguments)
    {
        return [
            $arguments[0]->format('Y-m-d H:i:s'),
            $arguments[1]->format('Y-m-d H:i:s'),
        ];
    }
}

class DummyClass
{
    use ProvidesBetweenArgumentsViaString;
}
