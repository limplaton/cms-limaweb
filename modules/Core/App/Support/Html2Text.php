<?php
 

namespace Modules\Core\App\Support;

class Html2Text
{
    /**
     * Convert HTML to Text
     *
     * @param  string  $html
     * @return string
     */
    public static function convert($html)
    {
        return \Soundasleep\Html2Text::convert($html, ['ignore_errors' => true]);
    }
}
