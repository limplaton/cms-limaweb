<?php
 

namespace Modules\Core\Tests\Feature\Models;

use Modules\Core\App\Models\ZapierHook;
use Modules\Users\App\Models\User;
use Tests\TestCase;

class ZapierHookTest extends TestCase
{
    public function test_zapier_hook_has_user()
    {
        $user = $this->createUser();

        $hook = new ZapierHook([
            'hook' => 'created',
            'action' => 'create',
            'resource_name' => 'resource',
            'user_id' => $user->id,
            'zap_id' => 123,
        ]);

        $hook->save();

        $this->assertInstanceOf(User::class, $hook->user);
    }
}
