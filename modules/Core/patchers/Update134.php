<?php
 

use Modules\Core\App\Models\Permission;
use Modules\Core\App\Models\Role;
use Modules\Core\App\Updater\UpdatePatcher;

return new class extends UpdatePatcher
{
    public function run(): void
    {
        if ($this->usingOldPermissionGuard()) {
            Permission::where('guard_name', 'api')->update(['guard_name' => 'sanctum']);
            Role::where('guard_name', 'api')->update(['guard_name' => 'sanctum']);
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        }
    }

    public function shouldRun(): bool
    {
        return $this->usingOldPermissionGuard();
    }

    protected function usingOldPermissionGuard(): bool
    {
        return Permission::where('guard_name', 'api')->exists() || Role::where('guard_name', 'api')->exists();
    }
};
