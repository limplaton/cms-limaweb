<?php
 

namespace Modules\Core\Tests\Feature\Filters;

use Illuminate\Database\Eloquent\Factories\Sequence;
use Modules\Core\App\Filters\Text;
use Modules\Core\Tests\Concerns\TestsFilters;
use Tests\Fixtures\Event;
use Tests\TestCase;

class TextFilterTest extends TestCase
{
    use TestsFilters;

    protected static $filter = Text::class;

    public function test_text_filter_rule_with_equal_operator()
    {
        Event::factory()->count(3)->state(new Sequence(
            ['description' => 'Dummy Description'],
            ['description' => 'Scheduled Event'],
            ['description' => 'Other Scheduled Event']
        ))->create();

        $result = $this->perform('description', 'equal', 'Dummy Description');

        $this->assertCount(1, $result);
    }

    public function test_text_filter_rule_with_not_equal_operator()
    {
        Event::factory()->count(3)->state(new Sequence(
            ['description' => 'Dummy Description'],
            ['description' => 'Scheduled Event'],
            ['description' => 'Other Scheduled Event']
        ))->create();

        $result = $this->perform('description', 'not_equal', 'Dummy Description');

        $this->assertCount(2, $result);
    }

    public function test_text_filter_rule_with_begins_with_operator()
    {
        Event::factory()->count(3)->state(new Sequence(
            ['description' => 'Dummy Description'],
            ['description' => 'Scheduled Event'],
            ['description' => 'Other Scheduled Event']
        ))->create();

        $result = $this->perform('description', 'begins_with', 'Scheduled');

        $this->assertCount(1, $result);
    }

    public function test_text_filter_rule_with_not_begins_with_operator()
    {
        Event::factory()->count(3)->state(new Sequence(
            ['description' => 'Dummy Description'],
            ['description' => 'Scheduled Event'],
            ['description' => 'Other Scheduled Event']
        ))->create();

        $result = $this->perform('description', 'not_begins_with', 'Scheduled');

        $this->assertCount(2, $result);
    }

    public function test_text_filter_rule_with_contains_operator()
    {
        Event::factory()->count(3)->state(new Sequence(
            ['description' => 'Dummy Description'],
            ['description' => 'Scheduled Event'],
            ['description' => 'Other Scheduled Event']
        ))->create();

        $result = $this->perform('description', 'contains', 'Event');

        $this->assertCount(2, $result);
    }

    public function test_text_filter_rule_with_not_contains_operator()
    {
        Event::factory()->count(3)->state(new Sequence(
            ['description' => 'Dummy Description'],
            ['description' => 'Scheduled Event'],
            ['description' => 'Other Scheduled Event']
        ))->create();

        $result = $this->perform('description', 'not_contains', 'Event');

        $this->assertCount(1, $result);
    }

    public function test_text_filter_rule_with_ends_with_operator()
    {
        Event::factory()->count(3)->state(new Sequence(
            ['description' => 'Dummy Description'],
            ['description' => 'Scheduled Event'],
            ['description' => 'Main Event']
        ))->create();

        $result = $this->perform('description', 'ends_with', 'Event');

        $this->assertCount(2, $result);
    }

    public function test_text_filter_rule_with_not_ends_with_operator()
    {
        Event::factory()->count(3)->state(new Sequence(
            ['description' => 'Dummy Description'],
            ['description' => 'Scheduled Event'],
            ['description' => 'Other Scheduled Event']
        ))->create();

        $result = $this->perform('description', 'not_ends_with', 'Description');

        $this->assertCount(2, $result);
    }

    public function test_text_filter_rule_with_is_not_empty_operator()
    {
        Event::factory()->count(3)->state(new Sequence(
            ['description' => ''],
            ['description' => 'Schedule'],
            ['description' => 'Dummy Description']
        ))->create();

        $result = $this->perform('description', 'is_not_empty');

        $this->assertCount(2, $result);
    }

    public function test_text_filter_rule_with_is_empty_operator()
    {
        Event::factory()->count(3)->state(new Sequence(
            ['description' => ''],
            ['description' => 'Schedule'],
            ['description' => 'Dummy Description']
        ))->create();

        $result = $this->perform('description', 'is_empty');

        $this->assertCount(1, $result);
    }

    public function test_text_filter_rule_with_is_null_operator()
    {
        Event::factory()->count(3)->state(new Sequence(
            ['date' => null],
            ['date' => date('Y-m-d')],
            ['date' => date('Y-m-d')]
        ))->create();

        $result = $this->perform('date', 'is_null');

        $this->assertCount(1, $result);
    }

    public function test_text_filter_rule_with_is_not_null_operator()
    {
        Event::factory()->count(3)->state(new Sequence(
            ['date' => null],
            ['date' => null],
            ['date' => date('Y-m-d')]
        ))->create();

        $result = $this->perform('date', 'is_not_null');

        $this->assertCount(1, $result);
    }
}
