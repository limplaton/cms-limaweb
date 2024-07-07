<?php
 

namespace Modules\Core\App\Common\VisibilityGroup;

use Illuminate\Database\Eloquent\Builder;
use Modules\Users\App\Models\User;

interface HasVisibilityGroups
{
    public function isVisible(User $user): bool;

    public function scopeVisible(Builder $query, User $user): void;
}
