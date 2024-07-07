<?php
 

namespace Modules\Translator\Tests\Feature;

use Modules\Translator\App\TranslationGroup;
use Symfony\Component\Finder\SplFileInfo;
use Tests\TestCase;

class TranslationGroupTest extends TestCase
{
    public function test_group_has_filename()
    {
        $group = new TranslationGroup($this->newGroupFile());

        $this->assertSame($group->filename(), 'validation.php');
    }

    public function test_group_has_fullpath()
    {
        $group = new TranslationGroup($this->newGroupFile());

        $this->assertSame($group->fullPath(), lang_path('en/validation.php'));
    }

    public function test_group_has_path()
    {
        $group = new TranslationGroup($this->newGroupFile());

        $this->assertSame($group->getPath(), lang_path('en'));
    }

    public function test_group_has_locale()
    {
        $group = new TranslationGroup($this->newGroupFile());

        $this->assertSame($group->locale(), 'en');
    }

    protected function newGroupFile()
    {
        return new SplFileInfo(lang_path('en/validation.php'), 'lang/en', 'lang/en/validation.php');
    }
}
