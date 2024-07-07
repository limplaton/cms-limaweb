<?php
 

namespace Modules\Translator\Tests\Feature;

use Illuminate\Support\Carbon;
use Tests\TestCase;

class GenerateJsonLanguageFileToolTest extends TestCase
{
    public function test_json_language_tool_can_be_executed()
    {
        $this->signIn();

        $this->postJson('/api/tools/json-language')->assertNoContent();

        $this->assertLessThanOrEqual(2, Carbon::parse(filemtime(config('translator.json')))->diffInSeconds());
    }
}
