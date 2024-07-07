<?php
 

namespace Modules\Core\App\Permissions;

use Closure;
use Illuminate\Support\Arr;
use Modules\Core\App\Models\Permission;

class PermissionsManager
{
    /**
     * Hold all of the registered groups
     */
    private array $groups = [];

    /**
     * Helper property for handling the group stacks
     */
    private array $groupStack = [];

    /**
     * Register callbacks
     */
    private array $callbacks = [];

    /**
     * Indicates whether the register callbacks were processed
     */
    private bool $callbacksProcessed = false;

    /**
     * Register new permissions group
     *
     * @param  string|array  $group
     */
    public function group($group, Closure $callback): void
    {
        $this->updateGroupStack($group);

        // Once we have updated the group stack, we'll load the provided permissions and
        // merge in the group's attributes when the permissions views are registered.
        // After we have created the permissions, we will pop the attributes off the stack.
        $this->registerGroup($callback);

        array_pop($this->groupStack);
    }

    /**
     * Register new permissions view to the group stack.
     */
    public function view(string $view, array $data): void
    {
        if ($this->hasGroupStack()
            && $lastStack = $this->getLastGroupStack()) {
            $this->checkPermissionViewArray($lastStack);
            $resourceName = $lastStack['name'];

            $this->configureViewPermissions($view, $resourceName, $data);
        }
    }

    /**
     * Create the missing permissions from database.
     */
    public function createMissing(): void
    {
        foreach ($this->all() as $permission) {
            Permission::findOrCreate($permission, 'sanctum');
        }
    }

    /**
     * Get all internal permission names.
     *
     * Includes only the permissions keys
     *
     * e.q. ['view_contacts', 'delete_contacts']
     */
    public function all(): array
    {
        $allByViews = data_get($this->groups(), '*.views.*.permissions');

        return array_keys(Arr::collapse($allByViews));
    }

    /**
     * Get all internal permission names with a label.
     *
     * e.q.  ['delete_contact' => 'Delete', 'view_contacts'=> 'View Contacts']
     */
    public function labeled(): array
    {
        $allByViews = data_get($this->groups(), '*.views.*.permissions');

        return Arr::collapse($allByViews);
    }

    /**
     * Determine if the permissions currently has a group stack.
     */
    public function hasGroupStack(): bool
    {
        return ! empty($this->groupStack);
    }

    /**
     * Get the last permissions group stack.
     *
     * @return array|null
     */
    public function getLastGroupStack()
    {
        return $this->groupStack[0] ?? null;
    }

    /**
     * Get all of the registered groups.
     */
    public function groups(): array
    {
        $this->runCallbacks();

        return $this->groups;
    }

    /**
     * Performs checks for the permission group array and sets default values
     *
     * @param  array  $lastStack
     * @return void
     */
    protected function checkPermissionViewArray($lastStack)
    {
        // Group name
        $name = $lastStack['name'];

        if (! array_key_exists($name, $this->groups)) {
            $this->groups[$name] = [
                'views' => [],
                'as' => $lastStack['as'],
            ];
        } elseif ($lastStack['as'] && ! $this->groups[$name]['as']) {
            $this->groups[$name]['as'] = $lastStack['as'];
        }
    }

    /**
     * Update the group stack with the given name.
     *
     * @param  string  $stack
     */
    protected function updateGroupStack($stack): void
    {
        $this->groupStack[] = ! is_array($stack) ? ['name' => $stack, 'as' => null] : $stack;
    }

    /**
     * Load the provided permissions.
     */
    protected function registerGroup(Closure $callback): void
    {
        $callback($this);
    }

    /**
     * Register permissions via the provided callback
     *
     * Because the permissions are mostly registered via service provider
     * when the application is not yet fully booted and the locale is not set
     * the recommended way to register new permissions is via this method
     *
     * In this case, when using any translate function for the 'as' will be
     * translated properly when getting all of the permissions for the front-end
     */
    public function register(Closure $callback): void
    {
        $this->callbacks[] = $callback;
    }

    /**
     * Run the provided register callbacks.
     */
    protected function runCallbacks(): void
    {
        if ($this->callbacksProcessed) {
            return;
        }

        foreach ($this->callbacks as $callbacks) {
            $callbacks($this);
        }

        $this->callbacks = [];

        $this->callbacksProcessed = true;
    }

    /**
     * Register new permissions view under the given resource group
     *
     * @param  string  $viewName
     * @param  string  $resourceName
     * @param  array  $data
     * @return void
     */
    protected function configureViewPermissions($viewName, $resourceName, $data)
    {
        /**
         * We will check if the group already exists, user is able to add additional permissions to an existing
         * just by providing the permissions parameter, note that the user is not allowed to modify other
         * parameters that were passed to the original group
         *
         * Sample usage:
         *
         *  Permissions::group($this->name(), function ($manager) {
         *   $manager->view('view', [
         *       'permissions' => [
         *           'view own attendes' => 'Where attending',
         *       ],
         *   ]);
         * });
         */
        $viewsKeys = array_column($this->groups[$resourceName]['views'], 'view');
        $existingViewIndex = array_search($viewName, $viewsKeys);

        if ($existingViewIndex !== false) {
            $group = $this->groups[$resourceName]['views'][$existingViewIndex];

            $group['permissions'] = array_merge($group['permissions'], $data['permissions']);
            $group['keys'] = array_keys($group['permissions']);
            $group['single'] = count($group['keys']) === 1;
            $group['as'] = $data['as'] ?? $group['as'];
            $group['revokeable'] = $data['revokeable'] ?? $group['single'];

            $this->groups[$resourceName]['views'][$existingViewIndex] = $group;

            return;
        }

        // Adding new view
        $keys = array_keys($data['permissions']);
        $single = count($keys) === 1;
        $revokeable = $data['revokeable'] ?? $single;  // View with single permission can be revoked

        $this->groups[$resourceName]['views'][] = [
            'revokeable' => $revokeable, // Whether the user can revoke the permission
            'keys' => $keys, // Keys are the permission internal names e.q. ['delete', 'create','view'];
            'single' => $single,
            'permissions' => $data['permissions'], // The available permissions for this view
            'as' => $data['as'] ?? null, // The view name to show e.q. View
            'view' => $viewName, // The view ID
        ];
    }
}
