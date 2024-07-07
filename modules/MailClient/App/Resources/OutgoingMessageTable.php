<?php
 

namespace Modules\MailClient\App\Resources;

use Modules\Core\App\Table\Column;
use Modules\Core\App\Table\DateTimeColumn;
use Modules\Core\App\Table\HasManyColumn;
use Modules\MailClient\App\Models\EmailAccountMessage;
use Modules\MailClient\App\Models\EmailAccountMessageAddress;

class OutgoingMessageTable extends IncomingMessageTable
{
    /**
     * Provides table available default columns
     */
    public function columns(): array
    {
        return [
            Column::make('subject', __('mailclient::inbox.subject'))->width('470px'),

            HasManyColumn::make('to', 'address', __('mailclient::inbox.to'))
                ->select('name')
                ->fillRowDataUsing(function (array &$row, EmailAccountMessage $message) {
                    $row['to'] = $message->to->map(
                        fn (EmailAccountMessageAddress $to) => ['address' => $to->address, 'name' => $to->name]
                    );
                }),

            DateTimeColumn::make('date', __('mailclient::inbox.date')),
        ];
    }
}
