<?php
 

namespace Modules\Core\Tests\Feature\Controller\Api;

use Akaunting\Money\Currency;
use Tests\TestCase;

class RetrieveCurrenciesTest extends TestCase
{
    public function test_unauthenticated_cannot_access_currency_endpoints()
    {
        $this->getJson('/api/currencies')->assertUnauthorized();
    }

    public function test_user_can_fetch_currencies()
    {
        $this->signIn();

        $this->getJson('/api/currencies')
            ->assertOk()
            ->assertJson(Currency::getCurrencies());
    }
}
