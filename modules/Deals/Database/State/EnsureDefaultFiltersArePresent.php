<?php
 

namespace Modules\Deals\Database\State;

use Illuminate\Support\Facades\DB;
use Modules\Core\App\Filters\Date;
use Modules\Core\App\Models\Filter;
use Modules\Deals\App\Enums\DealStatus;
use Modules\Deals\App\Filters\DealStatusFilter;
use Modules\Users\App\Filters\UserFilter;

class EnsureDefaultFiltersArePresent
{
    public function __invoke()
    {
        if (DB::table('filters')->where('flag', 'my-deals')->count() === 0) {
            $this->newModelInstance([
                'identifier' => 'deals',
                'name' => 'deals::deal.filters.my',
                'flag' => 'my-deals',
                'rules' => [
                    UserFilter::make()->setOperator('equal')->setValue('me')->toArray(),
                ],
            ])->save();
        }

        if (DB::table('filters')->where('flag', 'my-recently-assigned-deals')->count() === 0) {
            $this->newModelInstance([
                'identifier' => 'deals',
                'name' => 'deals::deal.filters.my_recently_assigned',
                'flag' => 'my-recently-assigned-deals',
                'rules' => [
                    UserFilter::make()->setOperator('equal')->setValue('me')->toArray(),
                    Date::make('owner_assigned_date')->setOperator('is')->setValue('this_month')->toArray(),
                ],
            ])->save();
        }

        if (DB::table('filters')->where('flag', 'deals-created-this-month')->count() === 0) {
            $this->newModelInstance([
                'identifier' => 'deals',
                'name' => 'deals::deal.filters.created_this_month',
                'flag' => 'deals-created-this-month',
                'rules' => [
                    Date::make('created_at')->setOperator('is')->setValue('this_month')->toArray(),
                ],
            ])->save();
        }

        if (DB::table('filters')->where('flag', 'won-deals')->count() === 0) {
            $this->newModelInstance([
                'identifier' => 'deals',
                'name' => 'deals::deal.filters.won',
                'flag' => 'won-deals',
                'rules' => [
                    DealStatusFilter::make()->setOperator('equal')->setValue(DealStatus::won->name)->toArray(),
                ],
            ])->save();
        }

        if (DB::table('filters')->where('flag', 'lost-deals')->count() === 0) {
            $this->newModelInstance([
                'identifier' => 'deals',
                'name' => 'deals::deal.filters.lost',
                'flag' => 'lost-deals',
                'rules' => [
                    DealStatusFilter::make()->setOperator('equal')->setValue(DealStatus::lost->name)->toArray(),
                ],
            ])->save();
        }

        if (DB::table('filters')->where('flag', 'open-deals')->count() === 0) {
            $this->newModelInstance([
                'identifier' => 'deals',
                'name' => 'deals::deal.filters.open',
                'flag' => 'open-deals',
                'rules' => [
                    DealStatusFilter::make()->setOperator('equal')->setValue(DealStatus::open->name)->toArray(),
                ],
            ])->save();
        }
    }

    protected function newModelInstance($attributes)
    {
        return new Filter(array_merge([
            'is_shared' => true,
            'is_readonly' => true,
        ], $attributes));
    }
}
