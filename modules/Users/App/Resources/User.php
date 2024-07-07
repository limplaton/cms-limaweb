<?php
 

namespace Modules\Users\App\Resources;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Modules\Core\App\Contracts\Resources\HasOperations;
use Modules\Core\App\Contracts\Resources\Tableable;
use Modules\Core\App\Fields\BelongsToMany;
use Modules\Core\App\Fields\Boolean;
use Modules\Core\App\Fields\DateTime;
use Modules\Core\App\Fields\FieldsCollection;
use Modules\Core\App\Fields\ID;
use Modules\Core\App\Fields\Text;
use Modules\Core\App\Fields\Timezone;
use Modules\Core\App\Http\Requests\ResourceRequest;
use Modules\Core\App\Models\Model;
use Modules\Core\App\Models\Role;
use Modules\Core\App\Resource\Events\ResourceRecordCreated;
use Modules\Core\App\Resource\Events\ResourceRecordDeleted;
use Modules\Core\App\Resource\Events\ResourceRecordUpdated;
use Modules\Core\App\Resource\Resource;
use Modules\Core\App\Rules\StringRule;
use Modules\Core\App\Rules\UniqueResourceRule;
use Modules\Core\App\Rules\ValidLocaleRule;
use Modules\Core\App\Settings\SettingsMenuItem;
use Modules\Core\App\Table\Column;
use Modules\Core\App\Table\Table;
use Modules\Users\App\Http\Resources\UserResource;
use Modules\Users\App\Services\UserService;

class User extends Resource implements HasOperations, Tableable
{
    /**
     * The column the records should be default ordered by when retrieving
     */
    public static string $orderBy = 'name';

    /**
     * The model the resource is related to
     */
    public static string $model = 'Modules\Users\App\Models\User';

    /**
     * Provide the resource table class instance.
     */
    public function table(Builder $query, ResourceRequest $request): Table
    {
        $table = new Table($query, $request);

        return $table->select(['avatar', 'super_admin'])
            ->appends(['avatar_url'])
            ->with(['teams', 'managedTeams'])
            ->customizeable()
            ->orderBy(static::$orderBy, static::$orderByDir);
    }

    /**
     * Get the resource search columns.
     */
    public function searchableColumns(): array
    {
        return ['name' => 'like', 'email'];
    }

    /**
     * Get the fields for index.
     */
    public function fieldsForIndex(): FieldsCollection
    {
        return (new FieldsCollection([
            Text::make('name', __('users::user.name'))
                ->tapIndexColumn(fn (Column $column) => $column
                    ->width('300px')
                    ->route(['name' => 'edit-user', 'params' => ['id' => '{id}']])
                    ->primary()),

            ID::make(),

            Text::make('email', __('users::user.email'))->tapIndexColumn(
                fn (Column $column) => $column->link('mailto:{email}')
            ),

            BelongsToMany::make('roles', __('core::role.roles'))
                ->labelKey('name')
                ->displayAsBadges()
                ->hidden(),

            BelongsToMany::make('teams', __('users::team.teams'))
                ->labelKey('name')
                ->displayAsBadges()
                ->hidden(),

            Timezone::make('timezone', __('core::app.timezone'))->hidden(),

            Boolean::make('super_admin', __('users::user.super_admin')),

            Boolean::make('access_api', __('core::api.access'))->hidden(),

            DateTime::make('created_at', __('core::app.created_at'))->hidden(),

            DateTime::make('updated_at', __('core::app.updated_at'))->hidden(),
        ]))->disableInlineEdit();
    }

    /**
     * Create resource record.
     */
    public function create(Model $model, ResourceRequest $request): Model
    {
        $user = (new UserService)->create($model, $request->all());

        ResourceRecordCreated::dispatch($user, $this);

        return $user;
    }

    /**
     * Update resource record.
     */
    public function update(Model $model, ResourceRequest $request): Model
    {
        $user = (new UserService)->update($model, $request->all());

        ResourceRecordUpdated::dispatch($user, $this);

        return $user;
    }

    /**
     * Delete resource record.
     */
    public function delete(Model $model, ResourceRequest $request): mixed
    {
        DB::beginTransaction();

        $transferDataTo = $request->integer('transfer_data_to') ?: null;

        (new UserService)->delete($model, $transferDataTo);

        ResourceRecordDeleted::dispatch($model, $this);

        DB::commit();

        return '';
    }

    /**
     * Get the json resource that should be used for json response
     */
    public function jsonResource(): string
    {
        return UserResource::class;
    }

    /**
     * Get the resource rules available for create and update
     */
    public function rules(ResourceRequest $request): array
    {
        return [
            'name' => ['required', StringRule::make()],
            'password' => [
                $request->route('resourceId') ? 'nullable' : 'required', 'confirmed', 'min:6',
            ],
            'roles' => ['sometimes', 'array', Rule::in(Role::select('name')->get()->pluck('name')->all())],
            'email' => ['required', StringRule::make(), 'email', UniqueResourceRule::make(static::$model)],
            'locale' => ['nullable', new ValidLocaleRule],
            'timezone' => ['required', 'string', 'timezone:all'],
            'time_format' => ['required', 'string', Rule::in(config('core.time_formats'))],
            'date_format' => ['required', 'string', Rule::in(config('core.date_formats'))],
        ];
    }

    /**
     * Provides the resource available actions
     */
    public function actions(ResourceRequest $request): array
    {
        return [
            (new \Modules\Users\App\Actions\UserDelete)->canSeeWhen('is-super-admin'),
        ];
    }

    /**
     * Register the settings menu items for the resource
     */
    public function settingsMenu(): array
    {
        return [
            SettingsMenuItem::make(__('users::user.users'), '/settings/users', 'Users')->order(41),
        ];
    }
}
