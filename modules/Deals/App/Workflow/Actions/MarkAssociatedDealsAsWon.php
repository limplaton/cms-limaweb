<?php
 

namespace Modules\Deals\App\Workflow\Actions;

use Modules\Core\App\Facades\ChangeLogger;
use Modules\Core\App\Workflow\Action;
use Modules\Deals\App\Models\Deal;

class MarkAssociatedDealsAsWon extends Action
{
    /**
     * Initialize MarkAssociatedDealsAsWon
     */
    public function __construct(protected string $relation)
    {
    }

    /**
     * Action name
     */
    public static function name(): string
    {
        return __('deals::deal.workflows.actions.mark_associated_deals_as_won');
    }

    /**
     * Run the trigger
     */
    public function run()
    {
        ChangeLogger::setCauser($this->workflow->creator);

        Deal::open()->whereHas($this->relation, function ($query) {
            $query->where($this->model->getKeyName(), $this->model->getKey());
        })->get()->each(function (Deal $deal) {
            $deal->broadcastToCurrentUser()->markAsWon();
        });

        ChangeLogger::setCauser(null);
    }

    /**
     * Action available fields
     */
    public function fields(): array
    {
        return [];
    }
}
