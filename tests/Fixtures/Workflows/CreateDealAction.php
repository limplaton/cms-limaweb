<?php

namespace Tests\Fixtures\Workflows;

use Modules\Core\App\Fields\Date;
use Modules\Core\App\Fields\Numeric;
use Modules\Core\App\Fields\Select;
use Modules\Core\App\Fields\Text;
use Modules\Core\App\Workflow\Action;
use Modules\Deals\App\Fields\Pipeline;
use Modules\Deals\App\Fields\PipelineStage;
use Modules\Users\App\Models\User;

class CreateDealAction extends Action
{
    /**
     * Run the trigger
     */
    public function run()
    {
    }

    /**
     * Action available fields
     */
    public function fields(): array
    {
        return [
            Text::make('name', 'Name'),
            Pipeline::make('Pipeline'),
            PipelineStage::make('Stage'),
            Numeric::make('amount', 'Amount'),
            Date::make('expected_close_date', 'Expected Close Date')->clearable(),
            Select::make('user_id')->options(function () {
                return User::select(['id', 'name'])->get()
                    ->map(fn ($user) => [
                        'value' => $user->id,
                        'label' => $user->name,
                    ]);
            }),
        ];
    }

    /**
     * Action name
     */
    public static function name(): string
    {
        return 'Create new deal';
    }
}
