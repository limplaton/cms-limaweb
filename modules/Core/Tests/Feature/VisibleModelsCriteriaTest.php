<?php
 

namespace Modules\Core\Tests\Feature;

use Modules\Core\App\Criteria\VisibleModelsCriteria;
use Modules\Core\App\Models\ModelVisibilityGroup;
use Modules\Deals\App\Models\Pipeline;
use Modules\Users\App\Models\Team;
use Modules\Users\App\Models\User;
use Tests\TestCase;

class VisibleModelsCriteriaTest extends TestCase
{
    public function test_visible_pipelines_criteria()
    {
        $user = User::factory()->has(Team::factory())->create();

        Pipeline::factory()
            ->has(
                ModelVisibilityGroup::factory()->teams()->hasAttached($user->teams->first()),
                'visibilityGroup'
            )
            ->create();

        $this->assertSame(1, Pipeline::criteria(new VisibleModelsCriteria($user))->count());
    }
}
