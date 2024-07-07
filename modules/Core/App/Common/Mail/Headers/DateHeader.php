<?php
 

namespace Modules\Core\App\Common\Mail\Headers;

use Carbon\Carbon;

class DateHeader extends Header
{
    /**
     * Get the header value
     */
    public function getValue(?string $tz = null): ?Carbon
    {
        $tz = $tz ?: config('app.timezone');

        $dateString = $this->value;

        // https://github.com/briannesbitt/Carbon/issues/685
        if (is_string($dateString)) {
            $dateString = trim(preg_replace('/\(.*$/', '', $dateString));
        }

        return $dateString ? Carbon::parse($dateString)->tz($tz) : null;
    }
}
