<?php
 

namespace Modules\Activities\App\Cards;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Activities\App\Models\Activity;
use Modules\Core\App\Card\TableAsyncCard;
use Modules\Core\App\Support\ProvidesBetweenArgumentsViaString;

class UpcomingUserActivities extends TableAsyncCard
{
    use ProvidesBetweenArgumentsViaString;

    /**
     * The default selected range
     *
     * @var string
     */
    public string|int|null $defaultRange = 'this_month';

    /**
     * Provide the query that will be used to retrieve the items.
     */
    public function query(Request $request): Builder
    {
        return Activity::with('type')
            ->upcoming()
            ->incomplete()
            ->where('user_id', Auth::id())
            ->whereBetween(Activity::dueDateQueryExpression(), $this->getBetweenArguments(
                $this->getCurrentRange($request)
            ))
            ->orderBy(Activity::dueDateQueryExpression());
    }

    /**
     * Provide the table fields
     */
    public function fields(): array
    {
        return [
            ['key' => 'title', 'label' => __('activities::activity.title'), 'sortable' => true],
            ['key' => 'due_date', 'label' => __('activities::activity.due_date'), 'sortable' => true],
        ];
    }

    /**
     * Get the searchable columns.
     */
    protected function getSearchableColumns(): array
    {
        return ['title' => 'like', 'type.name'];
    }

    /**
     * Get the columns that should be selected in the query
     */
    protected function selectColumns(Request $request): array
    {
        return array_merge(
            parent::selectColumns($request),
            // user_id is for authorization
            ['user_id']
        );
    }

    /**
     * Get the ranges available for the chart.
     */
    public function ranges(): array
    {
        return [
            'this_week' => __('core::dates.this_week'),
            'this_month' => __('core::dates.this_month'),
            'next_week' => __('core::dates.next_week'),
            'next_month' => __('core::dates.next_month'),
        ];
    }

    /**
     * Get the sort column
     */
    protected function getSortColumn(): Expression
    {
        return Activity::dueDateQueryExpression();
    }

    /**
     * The card name
     */
    public function name(): string
    {
        return __('activities::activity.cards.upcoming');
    }
}
