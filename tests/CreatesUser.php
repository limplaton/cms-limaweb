<?php

namespace Tests;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Modules\Core\App\Models\Permission;
use Modules\Core\App\Models\Role;
use Modules\Core\Database\Seeders\PermissionsSeeder;
use Modules\Users\App\Models\User;

trait CreatesUser
{
    /**
     * The permissions for the role.
     *
     * By default the role does not have any permissions.
     */
    protected array $withPermissionsTo = [];

    /**
     * The user create attributes to merge.
     */
    protected array $userAttributes = ['super_admin' => 1];

    /**
     * Default user attributes.
     */
    protected array $defaultUserAttributes = ['super_admin' => 1];

    /**
     * Create test user.
     *
     * @param  mixed  $parameters
     */
    protected function createUser(...$parameters): User|Collection
    {
        $user = User::factory(...$parameters)->create($this->userAttributes);

        if (count($this->withPermissionsTo) > 0) {
            $this->giveUserPermissions($user);
        }

        return $user;
    }

    /**
     * Sign in.
     */
    protected function signIn(?User $as = null): User
    {
        $user = $as ?: $this->createUser();

        if (! $as && count($this->withPermissionsTo) > 0) {
            $this->giveUserPermissions($user);
        }

        Sanctum::actingAs($user);

        return $user;
    }

    /**
     * Add user attributes.
     */
    protected function withUserAttrs(array $attributes): self
    {
        $this->userAttributes = array_merge($this->userAttributes, $attributes);

        return $this;
    }

    /**
     * As regular user helper.
     */
    protected function asRegularUser(): self
    {
        $this->withUserAttrs(['super_admin' => 0]);

        return $this;
    }

    /**
     * With permissions to.
     */
    protected function withPermissionsTo(array|string $permissions = []): self
    {
        $this->seed(PermissionsSeeder::class);

        $this->withPermissionsTo = (array) $permissions;

        return $this;
    }

    /**
     * Set user attributes.
     */
    protected function userAttrs(array $attributes): self
    {
        $this->userAttributes = $attributes;

        return $this;
    }

    /**
     * Assign the provide user permissions to the given user
     */
    private function giveUserPermissions(User $user): void
    {
        $role = $this->createRole();

        $role->givePermissionTo($this->withPermissionsTo);
        $user->assignRole($role);

        // Reset attributes in case $this->createUser() is called again
        $this->withPermissionsTo = [];
        $this->userAttributes = $this->defaultUserAttributes;
    }

    /**
     * Create new role.
     */
    protected function createRole(?string $name = null, string $guard = 'sanctum'): Role
    {
        return Role::findOrCreate($name ?: 'admin', $guard);
    }

    /**
     * Create new permission.
     */
    protected function createPermission(?string $name = null, string $guard = 'sanctum'): Permission
    {
        return Permission::findOrCreate($name ?: Str::random(), $guard);
    }
}
