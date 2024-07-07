<?php
 

namespace Modules\Core\App\Macros\Carbon;

use Illuminate\Support\Facades\Auth;
use Modules\Core\App\Contracts\Localizeable;
use Modules\Core\App\Support\Carbon;

trait FormatsDates
{
    /**
     * Format the current instance as a string in user's format.
     */
    public function formatForUser(string $format, ?Localizeable $user = null): string
    {
        return Carbon::inUserTimezone($this, $user)->translatedFormat($format);
    }

    /**
     * Format the current instance date in user's format.
     */
    public function formatDateForUser(?Localizeable $user = null): string
    {
        $user = $this->determineUser($user);

        return $this->formatForUser(
            $user?->getLocalDateFormat() ?? config('core.date_format'),
            $user
        );
    }

    /**
     * Format the current instance time in user's format.
     */
    public function formatTimeForUser(?Localizeable $user = null): string
    {
        $user = $this->determineUser($user);

        return $this->formatForUser(
            $user?->getLocalTimeFormat() ?? config('core.time_format'),
            $user
        );
    }

    /**
     * Format the current instance date and time in user's format.
     */
    public function formatDateTimeForUser(?Localizeable $user = null): string
    {
        return $this->formatDateForUser($user).' '.$this->formatTimeForUser($user);
    }

    /**
     * Display the difference for the current instance in a human-readable format.
     */
    public function diffForHumansForUser(?Localizeable $user = null): string
    {
        return Carbon::inUserTimezone($this, $user)->diffForHumans();
    }

    /**
     * Determine the user based on the optional user parameter or the authenticated user.
     */
    protected function determineUser(?Localizeable $user): ?Localizeable
    {
        return $user ?: Auth::user();
    }
}
