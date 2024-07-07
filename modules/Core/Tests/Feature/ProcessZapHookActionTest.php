<?php
 

namespace Modules\Core\Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Modules\Core\App\Models\ZapierHook;
use Modules\Core\App\Zapier\ProcessZapHookAction;
use Tests\TestCase;

class ProcessZapHookActionTest extends TestCase
{
    public function test_it_can_process_zapier_hook()
    {
        $hook = $this->createHook();

        Http::fake([
            'zapier.com/*' => Http::response(null, 200),
        ]);

        ProcessZapHookAction::dispatchSync($hook->hook, ['id' => 1, 'name' => 'John Doe']);

        Http::assertSent(function (Request $request) use ($hook) {
            return $request->url() == $hook->hook &&
                   $request['id'] == 1 &&
                   $request['name'] == 'John Doe';
        });
    }

    public function test_it_unsubscribe_from_the_hook_if_the_unsubscribe_error_code_is_thrown()
    {
        $hook = $this->createHook();

        Http::fake([
            'zapier.com/*' => Http::response(null, ProcessZapHookAction::STATUS_CODE_UNSUBSCRIBE),
        ]);

        try {
            ProcessZapHookAction::dispatchSync($hook->hook, ['test' => 'test']);
        } catch (RequestException $e) {
        } finally {
            $this->assertDatabaseMissing($hook->getTable(), ['id' => $hook->id]);
        }
    }

    protected function createHook()
    {
        return ZapierHook::create([
            'hook' => 'https://zapier.com/fake/hook/url',
            'zap_id' => 123,
            'data' => ['dummy-data' => 'dummy-value'],
            'user_id' => $this->createUser()->id,
            'resource_name' => 'events',
            'action' => 'create',
        ]);
    }
}
