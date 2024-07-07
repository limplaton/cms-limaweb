<?php
 

namespace Modules\MailClient\App\Resources;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Core\App\Filters\DateTime as DateTimeFilter;
use Modules\Core\App\Filters\Radio as RadioFilter;
use Modules\Core\App\Filters\Tags as TagsFilter;
use Modules\Core\App\Filters\Text as TextFilter;
use Modules\Core\App\Http\Requests\ResourceRequest;
use Modules\Core\App\Http\Resources\TagResource;
use Modules\Core\App\Models\Model;
use Modules\Core\App\Table\Column;
use Modules\Core\App\Table\DateTimeColumn;
use Modules\Core\App\Table\HasOneColumn;
use Modules\Core\App\Table\Table;
use Modules\MailClient\App\Models\EmailAccountMessage;

class IncomingMessageTable extends Table
{
    /**
     * Additional attributes to be appended with the response.
     */
    protected array $appends = ['is_read'];

    /**
     * Additional relations to eager load for the query.
     * Eager load the folders as the folders are used to create the path.
     */
    protected array $with = ['tags', 'folders'];

    /**
     * Additional database columns to select for the table query.
     */
    protected array $select = [
        'is_read',
        'email_account_id', // uri key for json resource
    ];

    /**
     * Provide the table available default columns.
     */
    public function columns(): array
    {
        return [
            Column::make('subject', __('mailclient::inbox.subject'))->width('470px'),

            HasOneColumn::make('from', 'address', __('mailclient::inbox.from'))
                ->select('name')
                ->fillRowDataUsing(function (array &$row, EmailAccountMessage $message) {
                    $row['from'] = $message->from ? [
                        'address' => $message->from->address,
                        'name' => $message->from->name,
                    ] : null;
                }),

            DateTimeColumn::make('date', __('mailclient::inbox.date')),
        ];
    }

    /**
     * Get the resource available Filters
     */
    public function filters(ResourceRequest $request): array
    {
        return [
            TextFilter::make('subject', __('mailclient::inbox.subject')),

            TextFilter::make('to', __('mailclient::inbox.to'))->withoutNullOperators()
                ->query(function ($builder, $value, $condition, $sqlOperator) {
                    return $builder->whereHas(
                        'from',
                        fn (Builder $query) => $query->where(
                            'address',
                            $sqlOperator['operator'],
                            $value,
                            $condition
                        )->orWhere(
                            'name',
                            $sqlOperator['operator'],
                            $value,
                            $condition
                        )
                    );
                }),

            TextFilter::make('from', __('mailclient::inbox.from'))->withoutNullOperators()
                ->query(function ($builder, $value, $condition, $sqlOperator) {
                    return $builder->whereHas(
                        'to',
                        fn (Builder $query) => $query->where(
                            'address',
                            $sqlOperator['operator'],
                            $value,
                            $condition
                        )->orWhere(
                            'name',
                            $sqlOperator['operator'],
                            $value,
                            $condition
                        )
                    );
                }),

            DateTimeFilter::make('date', __('mailclient::inbox.date')),

            TagsFilter::make('tags', __('core::tags.tags'))->forType(EmailAccountMessage::TAGS_TYPE),

            RadioFilter::make('is_read', __('mailclient::inbox.filters.is_read'))->options([
                true => __('core::app.yes'),
                false => __('core::app.no'),
            ]),
        ];
    }

    /**
     * Create new row for the response.
     */
    protected function createRow(Model $model, Collection $columns): array
    {
        $row = parent::createRow($model, $columns);

        $row['tags'] = TagResource::collection($model->tags);

        return $row;
    }

    /**
     * Boot table
     */
    public function boot(): void
    {
        $this->orderBy('date', 'desc')->provideRowClassUsing(function (array $row) {
            return [
                'read' => $row['is_read'],
                'unread' => ! $row['is_read'],
            ];
        });
    }
}
