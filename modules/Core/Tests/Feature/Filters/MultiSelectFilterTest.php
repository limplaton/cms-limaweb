<?php
 

namespace Modules\Core\Tests\Feature\Filters;

use Illuminate\Database\Eloquent\Factories\Sequence;
use Modules\Core\App\Filters\MultiSelect;
use Modules\Core\Tests\Concerns\TestsFilters;
use Tests\Fixtures\Event;
use Tests\TestCase;

class MultiSelectFilterTest extends TestCase
{
    use TestsFilters;

    protected static $filter = MultiSelect::class;

    public function test_multi_select_filter_rule_with_in_operator()
    {
        $events = Event::factory()->count(3)->state(new Sequence(
            ['title' => 'dummy-title-1'],
            ['title' => 'dummy-title-2'],
            ['title' => 'dummy-title-3']
        ))->create();

        $result = $this->perform('title', 'in', ['dummy-title-3', 'dummy-title-2']);

        $this->assertCount(2, $result);
    }

    public function test_multi_select_filter_rule_with_not_in_operator()
    {
        $events = Event::factory()->count(3)->state(new Sequence(
            ['title' => 'dummy-title-1'],
            ['title' => 'dummy-title-2'],
            ['title' => 'dummy-title-3']
        ))->create();

        $result = $this->perform('title', 'not_in', ['dummy-title-3', 'dummy-title-2']);

        $this->assertCount(1, $result);
    }

    public function test_multi_select_filter_rule_does_not_throw_error_when_no_value_provided()
    {
        static::$filter = MultiSelect::class;
        Event::factory()->create();
        $result = $this->perform('start', 'in', []);
        $this->assertCount(1, $result);
    }
}
