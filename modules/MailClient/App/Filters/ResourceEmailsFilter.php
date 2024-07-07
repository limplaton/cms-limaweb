<?php
 

namespace Modules\MailClient\App\Filters;

use Modules\Core\App\Filters\HasFilter;
use Modules\Core\App\Filters\Number;
use Modules\Core\App\Filters\Operand;

class ResourceEmailsFilter extends HasFilter
{
    /**
     * Initialize ResourceEmailsFilter class
     */
    public function __construct()
    {
        parent::__construct('emails', __('mailclient::inbox.inbox'));

        $this->setOperands([
            Operand::from(
                Number::make('total_unread', __('mailclient::inbox.unread_count'))->countableRelation('unreadEmailsForUser')
            ),
        ]);
    }
}
