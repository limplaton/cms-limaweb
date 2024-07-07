<?php
 

namespace Modules\Core\Tests\Feature\Controller;

use Tests\TestCase;

class PrivacyPolicyTest extends TestCase
{
    public function test_privacy_policy_can_be_viewed()
    {
        $policy = 'Test - Privacy Policy';

        settings()->set('privacy_policy', $policy)->save();

        $this->get('privacy-policy')->assertSee($policy);
    }
}
