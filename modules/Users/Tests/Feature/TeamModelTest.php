<?php
 

namespace Modules\Users\Tests\Feature;

use Modules\Users\App\Models\Team;
use Modules\Users\App\Models\User;
use Tests\TestCase;

class TeamModelTest extends TestCase
{
    public function test_team_has_users()
    {
        $team = Team::factory()->has(User::factory()->count(2))->create();

        $this->assertCount(2, $team->users);
    }
}
