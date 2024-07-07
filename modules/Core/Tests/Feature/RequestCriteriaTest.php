<?php
 

namespace Modules\Core\Tests\Feature;

use Illuminate\Support\Facades\Request;
use Modules\Core\App\Criteria\RequestCriteria;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Fixtures\Event;
use Tests\Fixtures\EventStatus;
use Tests\TestCase;

class RequestCriteriaTest extends TestCase
{
    /**
     * @return \Illuminate\Http\Request
     */
    protected function createCriteriaRequest($params)
    {
        $query = (is_array($params) ? http_build_query($params) : $params);

        return Request::create('/fake?'.$query, 'GET');
    }

    protected function query($params, $fields = [])
    {
        $model = new Event();

        $criteria = new RequestCriteria(
            $this->createCriteriaRequest($params),
        );

        $criteria->setSearchFields($fields);

        return $model->newQuery()->criteria($criteria);
    }

    public function test_it_aborts_when_all_provided_fields_are_not_searchable()
    {
        Event::factory()->create(['title' => 'title', 'description' => 'description']);

        try {
            $query = $this->query('q=title&search_fields=description', ['title' => '=']);

            $query->get();
        } catch (\Throwable $e) {
        }

        $this->assertEquals(
            new HttpException(403, 'None of the search fields were accepted. Acceptable search fields are: title,id'),
            $e
        );
    }

    public function test_it_accepts_search_fields_in_query_parameter()
    {
        Event::factory()->create(['title' => 'Event 1']);
        Event::factory()->create(['description' => 'Event 2']);

        $query = $this->query('q=title:Event 1;description:Event', ['title' => '=', 'description' => 'like']);

        $this->assertCount(2, $query->get());

        Event::factory()->create(['title' => 'unique', 'description' => 'Event 2']);

        $query = $this->query('q=title:John;description:Event 2&search_fields=title:like;description:=', [
            'title' => 'like',
            'description' => '=',
        ]);

        $this->assertCount(2, $query->get());
    }

    public function test_it_accepts_multiple_searchable_fields()
    {
        Event::factory()->create(['title' => 'Event', 'description' => 'Description']);
        Event::factory()->create(['description' => 'Event 1']);

        $query = $this->query('q=Event&search_fields=title:=;description:like', ['title' => '=', 'description' => 'like']);

        $this->assertCount(2, $query->get());
    }

    public function test_it_can_specify_the_search_match()
    {
        Event::factory()->create(['title' => 'Unique', 'description' => 'Description']);
        Event::factory()->create(['title' => 'Event', 'description' => 'Description']);

        $query = $this->query(
            'q=title:Unique;Description:Description&search_fields=title:like;Description:=&search_match=and', [
                'title' => 'like',
                'Description' => '=',
            ]);

        $this->assertCount(1, $query->get());
    }

    public function test_it_accept_searchable_fields_with_like_operator()
    {
        Event::factory()->create(['title' => 'Event 1', 'description' => 'Description 1']);
        Event::factory()->create(['title' => 'Should not be searched', 'description' => 'Event 1']);

        $query = $this->query('q=Event&search_fields=title:like', ['title' => 'like', 'description' => 'like']);

        $this->assertCount(1, $query->get());
    }

    public function test_it_uses_only_the_allowed_search_fields()
    {
        Event::factory()->create(['title' => 'Same']);
        Event::factory()->create(['description' => 'Same']);
        $query = $this->query('q=Same&search_fields=title', ['title' => '=']);

        $this->assertCount(1, $query->get());

        $query = $this->query('q=title:Same;description:Same', ['title' => '=']);

        $this->assertCount(1, $query->get());
    }

    public function test_when_no_searchable_fields_provided_it_uses_the_defined_ones()
    {
        Event::factory()->create(['title' => 'Event']);

        $query = $this->query('q=Event', ['title' => '=']);

        $this->assertCount(1, $query->get());
    }

    public function test_it_accept_searchable_fields_with_equal_operator()
    {
        Event::factory()->create(['title' => 'Event 1']);
        Event::factory()->create(['title' => 'Event 12']);

        $query = $this->query('q=Event 1&search_fields=title:=', ['title' => 'like']);

        $this->assertCount(1, $query->get());
    }

    public function test_it_can_take_specified_number()
    {
        Event::factory()->count(3)->create();

        $results = $this->query('take=2')->get();

        $this->assertCount(2, $results);
    }

    public function test_can_eager_load_relations()
    {
        Event::factory()->create();

        $results = $this->query('with=status;locations')->get();

        $this->assertTrue($results[0]->relationLoaded('status'));
        $this->assertTrue($results[0]->relationLoaded('locations'));

        // Single
        $results = $this->query('with=status')->get();

        $this->assertTrue($results[0]->relationLoaded('status'));

        // array
        $results = $this->query(['with' => ['status', 'locations']])->get();

        $this->assertTrue($results[0]->relationLoaded('status'));
        $this->assertTrue($results[0]->relationLoaded('locations'));
    }

    public function test_it_selects_only_the_provided_columns()
    {
        Event::factory()->create();

        $results = $this->query('select=id;title;description')->get();

        $this->assertNull($results[0]->start);
        $this->assertNull($results[0]->end);
        $this->assertNotNull($results[0]->id);
        $this->assertNotNull($results[0]->title);
        $this->assertNotNull($results[0]->description);

        // array
        $results = $this->query(['select' => ['id', 'title', 'description']])->get();

        $this->assertNull($results[0]->start);
        $this->assertNull($results[0]->end);
        $this->assertNotNull($results[0]->id);
        $this->assertNotNull($results[0]->title);
        $this->assertNotNull($results[0]->description);
    }

    public function test_it_applies_search_and_where_when_relation()
    {
        Event::factory()->for(EventStatus::factory([
            'name' => 'Confirmed',
        ]), 'status')->create();

        $query = $this->query('q=status.name:Confirmed&search_match=and', ['status.name' => '=']);

        $this->assertCount(1, $query->get());
    }

    public function test_it_applies_search_or_where_when_relation()
    {
        Event::factory()->for(EventStatus::factory([
            'name' => 'Confirmed',
        ]), 'status')->create();

        Event::factory()->create(['title' => 'Event']);

        $query = $this->query('q=title:Event;status.name:Confirmed&search_match=or', ['status.name' => '=', 'title' => '=']);

        $this->assertCount(2, $query->get());
    }

    public function test_it_applies_order_when_table_is_provided()
    {
        $this->markTestIncomplete();

        $event = Event::factory()->for(EventStatus::factory([
            'name' => 'Confirmed',
            'created_at' => now()->addDay(4),
        ]), 'status')->create();

        Event::factory()->create(['created_at' => now()->addDay(5)]);

        // With providing the table
        $results = $this->query(['order' => ['field' => 'events|created_at', 'direction' => 'desc']])->get();
        $this->assertEquals($event->id, $results[0]->id);

        // With custom foreign key name
        // $results = $this->query(['order' => ['field' => 'event_statuses:source_id|created_at', 'direction' => 'desc']])->get();
        // $this->assertEquals($event->id, $results[0]->id);
    }

    public function test_it_applies_order_on_multiple_provided_fields()
    {
        $event1 = Event::factory()->create(['title' => 'B', 'created_at' => now()->subDay(3)]);
        $event2 = Event::factory()->create(['created_at' => now()->subDay(5), 'title' => 'C']);
        $event3 = Event::factory()->create(['created_at' => now()->subDay(4), 'title' => 'A']);

        $results = $this->query(['order' => [
            ['field' => 'created_at', 'direction' => 'asc'],
            ['field' => 'title', 'direction' => 'desc'],
        ]])->get();

        $this->assertEquals($event2->id, $results[0]->id);
        $this->assertEquals($event3->id, $results[1]->id);
        $this->assertEquals($event1->id, $results[2]->id);
    }

    public function test_it_applies_the_provided_order()
    {
        $event = Event::factory()->create(['created_at' => now()->addDay(1)]);
        Event::factory()->create(['created_at' => now()->subDay(5)]);

        $results = $this->query('order=created_at|desc')->get();

        $this->assertEquals($event->id, $results[0]->id);

        $results = $this->query('order=created_at|asc')->get();
        $this->assertEquals($event->id, $results[1]->id);

        // default asc
        $results = $this->query('order=created_at')->get();
        $this->assertEquals($event->id, $results[1]->id);

        $results = $this->query(['order' => ['field' => 'created_at', 'direction' => 'asc']])->get();
        $this->assertEquals($event->id, $results[1]->id);

        $results = $this->query(['order' => ['field' => 'created_at', 'direction' => 'asc']])->get();
        $this->assertEquals($event->id, $results[1]->id);

        // default asc
        $results = $this->query(['order' => ['field' => 'created_at', 'direction' => '']])->get();
        $this->assertEquals($event->id, $results[1]->id);
    }
}
