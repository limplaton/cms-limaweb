<?php
 

namespace Modules\MailClient\App\Workflow\Actions;

use Modules\Core\App\Facades\Innoclapps;
use Modules\Core\App\Fields\Select;

class ResourcesSendEmailToField extends Select
{
    /**
     * Initialize new ResourcesSendEmailToField field
     */
    public function __construct()
    {
        parent::__construct('to');

        $this->rules('required')->withMeta([
            'attributes' => [
                'placeholder' => __('mailclient::mail.workflows.fields.to'),
            ], ]);
    }

    /**
     * Get the available resources from the field.
     */
    public function getToResources(): array
    {
        return collect($this->options)->mapWithKeys(function ($option, $key) {
            return [$key => Innoclapps::resourceByName($option['resource'])];
        })->all();
    }

    /**
     * Resolve the field options
     */
    public function resolveOptions(): array
    {
        return collect(parent::resolveOptions())->map(function ($option) {
            return [
                $this->labelKey => $option['label']['label'],
                $this->valueKey => $option['value'],
            ];
        })->all();
    }
}
