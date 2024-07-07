<?php
 

namespace Modules\Users\App\Filters;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Modules\Core\App\Filters\Select;
use Modules\Users\App\Models\User;

class UserFilter extends Select
{
    /**
     * Initialize new User instance.
     *
     * @param  string|null  $label
     * @param  string|null  $field
     */
    public function __construct($label = null, $field = null)
    {
        parent::__construct($field ?? 'user_id', $label ?? __('users::user.user'));

        $this->valueKey('id')->labelKey('name');
    }

    /**
     * Provides the User filter options.
     */
    public function resolveOptions(): array
    {
        // The user filter is the most used field in the APP,
        // in this case we will make sure to cache them in an array.
        return Cache::store('array')->rememberForever('user-filter-options', function () {
            return User::select([$this->valueKey, $this->labelKey])
                ->orderBy($this->labelKey)
                ->get()
                ->map(function ($user) {
                    if ($user->is(Auth::user())) {
                        return [
                            'id' => 'me',
                            'name' => __('core::filters.me'),
                        ];
                    }

                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                    ];
                })
                ->all();
        });

    }
}
