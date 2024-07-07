<?php
 

namespace Modules\Core\App\Http\Requests;

use Modules\Core\App\Actions\Action;
use Modules\Core\App\Actions\ActionFields;
use Modules\Core\App\Fields\Field;
use Modules\Core\App\Fields\FieldsCollection;
use Modules\Core\App\Table\Table;

class ActionRequest extends ResourceRequest
{
    use RunsValidationCallbacks,
        ValidatesFields {
            ValidatesFields::rules as fieldsRules;
        }

    /**
     * Get the action for the request.
     */
    public function action(): Action
    {
        return once(function () {
            return $this->availableActions()->first(function ($action) {
                return $action->uriKey() == $this->route('action');
            }) ?: abort($this->actionExists() ? 403 : 404);
        });
    }

    /**
     * Run the action for the current request.
     *
     * @return mixed
     */
    public function run()
    {
        return $this->action()->run($this, $this->newQuery());
    }

    /**
     * Resolve the request fields.
     */
    public function resolveFields(): ActionFields
    {
        return new ActionFields($this->getFields()->reject(function (Field $field) {
            return $this->missing($field->requestAttribute());
        })->toData($this));
    }

    /**
     * Perform validation to the action fields.
     */
    public function performValidation(): void
    {
        $this->validate($this->fieldsRules(), $this->messages(), $this->attributes());
    }

    /**
     * Get the available fields for the request.
     */
    public function getFields(): FieldsCollection
    {
        return $this->action()->resolveFields($this);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'ids' => 'array',
        ];
    }

    /**
     * Get the possible actions for the request.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function availableActions()
    {
        // We will get the actual global actions that are defined for the resource
        // as well any actions defined in the resource table classes, as if we don't
        // check the defined actions in table a 404 error will be thrown as the action won't exists
        // in the resource e.q. possible usage, custom actions defined in resource table class or
        // trashed resource table restore and delete actions
        return $this->resource()->resolveActions($this)->merge(
            $this->resourceTable()->resolveActions($this)->all()
        );
    }

    /**
     * Get the resource table class.
     */
    protected function resourceTable(): Table
    {
        return $this->boolean('trashed') ?
            $this->resource()->resolveTrashedTable(app(ResourceRequest::class)) :
            $this->resource()->resolveTable(app(ResourceRequest::class));
    }

    /**
     * Determine if the specified action exists at all.
     */
    protected function actionExists(): bool
    {
        $definedActionsFromTable = $this->resourceTable()->actions($this);

        if (! is_array($definedActionsFromTable)) {
            $definedActionsFromTable = $definedActionsFromTable->all();
        }

        return collect($this->resource()->actions($this))
            ->merge($definedActionsFromTable)
            ->contains(
                fn ($action) => $action->uriKey() == $this->route('action')
            );
    }
}
