<?php
 

namespace Modules\WebForms\Tests\Unit;

use Modules\WebForms\App\Services\PhoneMerger;
use PHPUnit\Framework\TestCase;

class PhoneMergerTest extends TestCase
{
    public function test_merge_phones()
    {
        $oldPhones = [
            ['number' => '23223', 'type' => 'work'],
            ['number' => '464646', 'type' => 'mobile'],
        ];

        $newPhones = [
            ['number' => '23223', 'type' => 'mobile'],
            ['number' => '73453455', 'type' => 'work'],
        ];

        $expectedResult = [
            ['number' => '23223', 'type' => 'mobile'],
            ['number' => '464646', 'type' => 'mobile'],
            ['number' => '73453455', 'type' => 'work'],
        ];

        $result = (new PhoneMerger)->merge($oldPhones, $newPhones);

        $this->assertEquals($expectedResult, $result, 'The merged phones array does not match the expected result.');
    }
}
