<?php
 

namespace Modules\Activities\App\Fields;

use Modules\Core\App\Fields\DateTime;

class NextActivityDate extends DateTime
{
    /**
     * Initialize new NextActivityDate instance
     */
    public function __construct()
    {
        parent::__construct('next_activity_date', __('activities::activity.next_activity_date'));

        $this->exceptOnForms()
            ->excludeFromDetail()
            ->excludeFromSettings()
            ->excludeFromImport()
            ->readOnly(true)
            ->help(__('activities::activity.next_activity_date_info'))
            ->hidden();
    }
}
