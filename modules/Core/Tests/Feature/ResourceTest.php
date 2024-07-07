<?php
 

namespace Modules\Core\Tests\Feature;

use Modules\Core\App\Facades\Innoclapps;
use Tests\Fixtures\EventResource;
use Tests\TestCase;

class ResourceTest extends TestCase
{
    public function test_it_can_find_resource_by_model()
    {
        $this->assertNotNull(Innoclapps::resourceByModel(EventResource::$model));
        $this->assertNotNull(Innoclapps::resourceByModel(resolve(EventResource::$model)));
    }

    public function test_it_can_find_globally_searchable_resources()
    {
        $this->assertNotNull(Innoclapps::globallySearchableResources()->first(function ($resource) {
            return $resource->name() === 'events';
        }));
    }
}
