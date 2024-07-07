<?php
 

namespace Modules\WebForms\App\Services;

use Modules\Core\App\Facades\Innoclapps;
use Modules\Core\App\Models\Changelog;
use Modules\Core\App\Support\Carbon;

class FormSubmission
{
    /**
     * Initialize new FormSubmission instance.
     */
    public function __construct(protected Changelog $changelog)
    {
    }

    /**
     * Get the web form submission data.
     *
     * @return \Illuminate\Support\Collection
     */
    public function data()
    {
        return $this->changelog->properties;
    }

    /**
     * Parse the displayable value.
     *
     * @param  string|null  $value
     * @param  array  $property
     * @return string
     */
    protected function parseValue($value, $property)
    {
        if (! empty($value)) {
            if (isset($property['dateTime'])) {
                $value = Carbon::parse($value)->formatDateTimeForUser();
            } elseif (isset($property['date'])) {
                $value = Carbon::parse($value)->formatDateForUser();
            }
        }

        return $value !== null ? $value : '/';
    }

    /**
     * __toString
     */
    public function __toString(): string
    {
        $payload = '';

        foreach ($this->data() as $property) {
            $payload .= '<div>';
            $payload .= Innoclapps::resourceByName($property['resourceName'])->singularLabel();
            $payload .= '  '.'<span style="font-weight:bold;">'.$property['label'].'</span>';
            $payload .= '</div>';
            $payload .= '<p style="margin-top:5px;">';
            $payload .= $this->parseValue($property['value'], $property);
            $payload .= '</p>';
        }

        return $payload;
    }
}
