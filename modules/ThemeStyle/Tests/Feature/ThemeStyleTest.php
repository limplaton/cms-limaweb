<?php
 

namespace Modules\ThemeStyle\Tests\Feature;

use Tests\TestCase;

class ThemeStyleTest extends TestCase
{
    public function test_it_loads_custom_theme_styles()
    {
        settings([
            'theme_style' => '{"neutral":{"valueStop":500,"lMax":100,"lMin":0,"hex":"#546783","swatches":[{"hex":"#ECEFF3","stop":50},{"hex":"#DADFE7","stop":100},{"hex":"#B8C2D1","stop":200},{"hex":"#92A2B9","stop":300},{"hex":"#7085A3","stop":400},{"hex":"#546783","stop":500},{"hex":"#44536A","stop":600},{"hex":"#323D4E","stop":700},{"hex":"#222935","stop":800},{"hex":"#101419","stop":900}]}}']);

        $this->get('theme-style')
            ->assertOk()
            ->assertContent(':root {--color-neutral-50: 236, 239, 243 !important;--color-neutral-100: 218, 223, 231 !important;--color-neutral-200: 184, 194, 209 !important;--color-neutral-300: 146, 162, 185 !important;--color-neutral-400: 112, 133, 163 !important;--color-neutral-500: 84, 103, 131 !important;--color-neutral-600: 68, 83, 106 !important;--color-neutral-700: 50, 61, 78 !important;--color-neutral-800: 34, 41, 53 !important;--color-neutral-900: 16, 20, 25 !important;}');
    }

    public function test_it_does_not_throw_error_when_no_theme_style_set()
    {
        settings(['theme_style' => null]);

        $this->get('theme-style')
            ->assertNoContent();
    }
}
