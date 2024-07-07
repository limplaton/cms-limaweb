<?php
 

namespace Modules\Core\Tests\Feature\Filters;

use Illuminate\Database\Eloquent\Factories\Sequence;
use Modules\Core\App\Filters\Radio;
use Modules\Core\Tests\Concerns\TestsFilters;
use Tests\Fixtures\Event;
use Tests\TestCase;

class RadioFilterTest extends TestCase
{
    use TestsFilters;

    protected static $filter = Radio::class;

    public function test_radio_filter_rule_with_equal_operator()
    {
        Event::factory()->count(2)->state(new Sequence(
            ['total_guests' => 1],
            ['total_guests' => 2]
        ))->create();

        $result = $this->perform('total_guests', 'equal', 1);

        $this->assertEquals($result[0]->total_guests, 1);
        $this->assertCount(1, $result);
    }

    public function test_radio_filter_does_not_throw_error_when_no_value_provided()
    {
        Event::factory()->count(2)->create();

        $result = $this->perform('start', 'equal', '');
        $this->assertCount(0, $result);
    }
}
