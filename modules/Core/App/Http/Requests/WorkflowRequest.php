<?php
 

namespace Modules\Core\App\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Modules\Core\App\Contracts\Workflow\FieldChangeTrigger;
use Modules\Core\App\Fields\Field;
use Modules\Core\App\Rules\StringRule;
use Modules\Core\App\Workflow\Trigger;
use Modules\Core\App\Workflow\Workflows;

class WorkflowRequest extends FormRequest
{
    /**
     * Trigger instance.
     */
    protected ?Trigger $trigger = null;

    /**
     * Create properly formatted data for storage.
     */
    public function createData(): array
    {
        return array_merge_recursive(
            $this->only(['trigger_type', 'action_type', 'title', 'description', 'is_active']),
            // Get the action available fields values
            ['data' => array_merge(
                $this->only($this->fieldsAttributes()),
                $this->isFieldChangeTrigger() ?
                    value(function ($changeField) {
                        return [
                            $changeField->attribute => $this->{$changeField->attribute},
                        ];
                    }, $this->getTrigger()::changeField()) : []
            )],
            ['created_by' => $this->user()->id]
        );
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return array_merge(
            [
                'trigger_type' => ['required', Rule::in(Workflows::availableTriggers())],
                'action_type' => ['required', function (string $attribute, mixed $value, Closure $fail) {
                    if (! $this->getTrigger()) {
                        return;
                    }

                    if (is_null($this->getTrigger()->getAction($value))) {
                        $fail('validation.in_array')->translate([
                            'attribute' => 'action',
                            'other' => 'the trigger available actions',
                        ]);
                    }
                }],
                'title' => ['required', StringRule::make()],
                'description' => [StringRule::make()],
                'is_active' => 'boolean',
            ],
            $this->isFieldChangeTrigger() ? [$this->getTrigger()::changeField()->attribute => 'required'] : [],
            $this->getRulesFromActionFields()
        );
    }

    /**
     * Get the rules from the action fields.
     */
    protected function getRulesFromActionFields(): array
    {
        if (! $this->getTrigger()) {
            return [];
        }

        return $this->actionFields()->mapWithKeys(fn (Field $field) => $field->getRules())->all();
    }

    /**
     * Get the trigger for the request.
     *
     * @return \Modules\Core\App\Workflow\Trigger|\Modules\Core\App\Contracts\Workflow\FieldChangeTrigger|null
     */
    public function getTrigger()
    {
        if (! $this->trigger && $this->trigger_type && in_array($this->trigger_type, Workflows::availableTriggers())) {
            $this->trigger = Workflows::newTriggerInstance($this->trigger_type);
        }

        return $this->trigger;
    }

    /**
     * Check whether the trigger is field change.
     */
    public function isFieldChangeTrigger(): bool
    {
        return $this->getTrigger() instanceof FieldChangeTrigger;
    }

    /**
     * Get the action fields.
     */
    public function actionFields(): Collection
    {
        $fields = collect([]);

        if (! $this->action_type) {
            return $fields;
        }

        if ($action = $this->getTrigger()->getAction($this->action_type)) {
            $fields = $fields->merge($action->fields());
        }

        return $fields;
    }

    /**
     * Get the action fields attributes.
     */
    public function fieldsAttributes(): array
    {
        return $this->actionFields()->map(fn (Field $field) => $field->requestAttribute())->all();
    }
}
