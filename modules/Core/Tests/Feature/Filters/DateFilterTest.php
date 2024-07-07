<?php
 

namespace Modules\Core\Tests\Feature\Filters;

use Illuminate\Database\Eloquent\Factories\Sequence;
use Modules\Core\App\Filters\Date;
use Modules\Core\App\Filters\DateTime;
use Modules\Core\App\Support\ProvidesBetweenArgumentsViaString;
use Modules\Core\Tests\Concerns\TestsFilters;
use Tests\Fixtures\Event;
use Tests\TestCase;

class DateFilterTest extends TestCase
{
    use ProvidesBetweenArgumentsViaString,
        TestsFilters;

    protected static $filter;

    public function test_date_filter_rule_with_equal_operator()
    {
        static::$filter = DateTime::class;

        Event::factory()->count(2)->state(new Sequence(
            ['start' => $lastWeek = date('Y-m-d', strtotime('last week'))],
            ['start' => date('Y-m-d', strtotime('next week'))]
        ))->create();

        $result = $this->perform('start', 'equal', $lastWeek);

        $this->assertCount(1, $result);

        static::$filter = Date::class;

        Event::factory()->count(2)->state(new Sequence(
            ['date' => $lastWeek = date('Y-m-d', strtotime('last week'))],
            ['date' => date('Y-m-d', strtotime('next week'))]
        ))->create();

        $result = $this->perform('date', 'equal', $lastWeek);

        $this->assertCount(1, $result);
    }

    public function test_date_filter_rule_with_not_equal_operator()
    {
        static::$filter = DateTime::class;

        Event::factory()->count(2)->state(new Sequence(
            ['start' => $lastWeek = date('Y-m-d', strtotime('last week'))],
            ['start' => date('Y-m-d', strtotime('next week'))]
        ))->create();

        $result = $this->perform('start', 'not_equal', $lastWeek);

        $this->assertCount(1, $result);

        static::$filter = Date::class;

        Event::factory()->count(2)->state(new Sequence(
            ['date' => $lastWeek = date('Y-m-d', strtotime('last week'))],
            ['date' => date('Y-m-d', strtotime('next week'))]
        ))->create();

        $result = $this->perform('date', 'not_equal', $lastWeek);

        $this->assertCount(1, $result);
    }

    public function test_date_filter_rule_with_less_operator()
    {
        static::$filter = DateTime::class;

        Event::factory()->count(2)->state(new Sequence(
            ['start' => date('Y-m-d', strtotime('last week'))],
            ['start' => $nextWeek = date('Y-m-d', strtotime('next week'))]
        ))->create();

        $result = $this->perform('start', 'less', $nextWeek);

        $this->assertCount(1, $result);

        static::$filter = Date::class;

        Event::factory()->count(2)->state(new Sequence(
            ['date' => date('Y-m-d', strtotime('last week'))],
            ['date' => $nextWeek = date('Y-m-d', strtotime('next week'))]
        ))->create();

        $result = $this->perform('date', 'less', $nextWeek);

        $this->assertCount(1, $result);
    }

    public function test_date_filter_rule_with_less_or_equal_operator()
    {
        static::$filter = DateTime::class;

        Event::factory()->count(3)->state(new Sequence(
            ['start' => date('Y-m-d', strtotime('last week'))],
            ['start' => date('Y-m-d', strtotime('second day next week'))],
            ['start' => $nextWeek = date('Y-m-d', strtotime('next week'))]
        ))->create();

        $result = $this->perform('start', 'less_or_equal', $nextWeek);

        $this->assertCount(2, $result);

        static::$filter = Date::class;

        Event::factory()->count(3)->state(new Sequence(
            ['date' => date('Y-m-d', strtotime('last week'))],
            ['start' => date('Y-m-d', strtotime('second day next week'))],
            ['date' => $nextWeek = date('Y-m-d', strtotime('next week'))]
        ))->create();

        $result = $this->perform('date', 'less_or_equal', $nextWeek);

        $this->assertCount(2, $result);
    }

    public function test_date_filter_rule_with_greater_operator()
    {
        static::$filter = DateTime::class;

        Event::factory()->count(2)->state(new Sequence(
            ['start' => $lastWeek = date('Y-m-d', strtotime('last week'))],
            ['start' => date('Y-m-d', strtotime('next week'))]
        ))->create();

        $result = $this->perform('start', 'greater', $lastWeek);

        $this->assertCount(1, $result);

        static::$filter = Date::class;

        Event::factory()->count(2)->state(new Sequence(
            ['date' => $lastWeek = date('Y-m-d', strtotime('last week'))],
            ['date' => date('Y-m-d', strtotime('next week'))]
        ))->create();

        $result = $this->perform('date', 'greater', $lastWeek);

        $this->assertCount(1, $result);
    }

    public function test_date_filter_rule_with_greater_or_equal_operator()
    {
        static::$filter = DateTime::class;

        Event::factory()->count(3)->state(new Sequence(
            ['start' => date('Y-m-d', strtotime('last month'))],
            ['start' => date('Y-m-d', strtotime('second day next week'))],
            ['start' => $nextWeek = date('Y-m-d', strtotime('next week'))]
        ))->create();

        $result = $this->perform('start', 'greater_or_equal', $nextWeek);

        $this->assertCount(2, $result);

        static::$filter = Date::class;

        Event::factory()->count(3)->state(new Sequence(
            ['date' => date('Y-m-d', strtotime('last month'))],
            ['date' => date('Y-m-d', strtotime('second day next week'))],
            ['date' => $nextWeek = date('Y-m-d', strtotime('next week'))]
        ))->create();

        $result = $this->perform('date', 'greater_or_equal', $nextWeek);

        $this->assertCount(2, $result);
    }

    public function test_date_filter_rule_with_between_operator()
    {
        static::$filter = DateTime::class;

        $thisMonth = date('Y-m-d', strtotime('last month'));
        $nextMonth = date('Y-m-d', strtotime('next month'));

        Event::factory()->count(4)->state(new Sequence(
            ['start' => date('Y-m-d', strtotime('last year'))],
            ['start' => date('Y-m-d', strtotime('last year'))],
            ['start' => $thisMonth],
            ['start' => $nextMonth]
        ))->create();

        $result = $this->perform('start', 'between', [$thisMonth, $nextMonth]);

        $this->assertCount(2, $result);

        static::$filter = Date::class;

        Event::factory()->count(3)->state(new Sequence(
            ['date' => date('Y-m-d', strtotime('last year'))],
            ['date' => date('Y-m-d', strtotime('last year'))],
            ['date' => $thisMonth],
            ['date' => $nextMonth]
        ))->create();

        $result = $this->perform('date', 'between', [$thisMonth, $nextMonth]);
    }

    public function test_date_filter_rule_with_not_between_operator()
    {
        static::$filter = DateTime::class;

        $thisMonth = date('Y-m-d', strtotime('last month'));
        $nextMonth = date('Y-m-d', strtotime('next month'));

        Event::factory()->count(4)->state(new Sequence(
            ['start' => date('Y-m-d', strtotime('last year'))],
            ['start' => date('Y-m-d', strtotime('last year'))],
            ['start' => $thisMonth],
            ['start' => $nextMonth]
        ))->create();

        $result = $this->perform('start', 'not_between', [$thisMonth, $nextMonth]);

        $this->assertCount(2, $result);

        static::$filter = Date::class;

        Event::factory()->count(3)->state(new Sequence(
            ['date' => date('Y-m-d', strtotime('last year'))],
            ['date' => date('Y-m-d', strtotime('last year'))],
            ['date' => $thisMonth],
            ['date' => $nextMonth]
        ))->create();

        $result = $this->perform('date', 'not_between', [$thisMonth, $nextMonth]);
    }

    public function test_date_filter_rule_with_is_operator()
    {
        // Create floating event so we can always have returned 1 result based on the applied filter
        // If this event is queried, means that the test will probably fail
        Event::factory()->create([
            'start' => date('Y-m-d H:i:s', strtotime('25 years ago')),
            'end' => date('Y-m-d H:i:s', strtotime('25 years ago')),
        ]);

        foreach ([Date::class, DateTime::class] as $filter) {
            static::$filter = $filter;

            $attribute = is_a($filter, Date::class, true) ? 'date' : 'start';

            $isOperatorOptions = app($filter, [
                'field' => $attribute,
            ])->isOperatorOptions();

            foreach ($isOperatorOptions as $isOperatorValue => $label) {
                $betweenArguments = $this->getBetweenArguments($isOperatorValue);

                $event = Event::factory()->create([
                    $attribute => $betweenArguments[0],
                ]);

                $result = $this->perform($attribute, 'is', $isOperatorValue);
                $event->delete();
                $this->assertCount(1, $result);
            }
        }
    }

    public function test_date_filter_does_not_throw_error_when_not_value_is_passed()
    {
        static::$filter = DateTime::class;

        $result = $this->perform('start', 'equal', '');
        $this->assertCount(0, $result);

        static::$filter = Date::class;

        $result = $this->perform('start', 'equal', '');
        $this->assertCount(0, $result);
    }
}
