<?php
 

namespace Modules\Translator\Tests\Feature;

use Tests\TestCase;

class GenerateJsonLanguageFileCommandTest extends TestCase
{
    public function test_it_generates_json_language_file()
    {
        $this->artisan('translator:json')
            ->assertSuccessful()
            ->expectsOutput('Language file generated successfully.');
    }
}
