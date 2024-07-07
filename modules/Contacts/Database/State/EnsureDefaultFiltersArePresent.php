<?php
 

namespace Modules\Contacts\Database\State;

use Illuminate\Support\Facades\DB;
use Modules\Core\App\Filters\Date;
use Modules\Core\App\Models\Filter;
use Modules\Users\App\Filters\UserFilter;

class EnsureDefaultFiltersArePresent
{
    public function __invoke()
    {
        foreach (['Contact', 'Company'] as $resource) {
            $this->{'seed'.$resource.'Filters'}();
        }
    }

    public function seedContactFilters()
    {
        if (DB::table('filters')->where('flag', 'my-contacts')->count() === 0) {
            $this->newModelInstance([
                'identifier' => 'contacts',
                'name' => 'contacts::contact.filters.my',
                'flag' => 'my-contacts',
                'rules' => [
                    UserFilter::make()->setOperator('equal')->setValue('me')->toArray(),
                ],
            ])->save();
        }

        if (DB::table('filters')->where('flag', 'my-recently-assigned-contacts')->count() === 0) {
            $this->newModelInstance([
                'identifier' => 'contacts',
                'name' => 'contacts::contact.filters.my_recently_assigned',
                'flag' => 'my-recently-assigned-contacts',
                'rules' => [
                    UserFilter::make()->setOperator('equal')->setValue('me')->toArray(),
                    Date::make('owner_assigned_date')->setOperator('is')->setValue('this_month')->toArray(),
                ],
            ])->save();
        }
    }

    public function seedCompanyFilters()
    {
        if (DB::table('filters')->where('flag', 'my-companies')->count() === 0) {
            $this->newModelInstance([
                'identifier' => 'companies',
                'name' => 'contacts::company.filters.my',
                'flag' => 'my-companies',
                'rules' => [
                    UserFilter::make()->setOperator('equal')->setValue('me')->toArray(),
                ],
            ])->save();
        }

        if (DB::table('filters')->where('flag', 'my-recently-assigned-companies')->count() === 0) {
            $this->newModelInstance([
                'identifier' => 'companies',
                'name' => 'contacts::company.filters.my_recently_assigned',
                'flag' => 'my-recently-assigned-companies',
                'rules' => [
                    UserFilter::make()->setOperator('equal')->setValue('me')->toArray(),
                    Date::make('owner_assigned_date')->setOperator('is')->setValue('this_month')->toArray(),
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
