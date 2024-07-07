<?php
 

namespace Modules\Documents\App\Workflow\Triggers;

use Modules\Core\App\Contracts\Workflow\FieldChangeTrigger;
use Modules\Core\App\Contracts\Workflow\ModelTrigger;
use Modules\Core\App\Fields\Select;
use Modules\Core\App\Workflow\Actions\WebhookAction;
use Modules\Core\App\Workflow\Trigger;
use Modules\Deals\App\Workflow\Actions\MarkAssociatedDealsAsLost;
use Modules\Deals\App\Workflow\Actions\MarkAssociatedDealsAsWon;
use Modules\Documents\App\Enums\DocumentStatus;
use Modules\MailClient\App\Workflow\Actions\ResourcesSendEmailToField;
use Modules\MailClient\App\Workflow\Actions\SendEmailAction;

class DocumentStatusChanged extends Trigger implements FieldChangeTrigger, ModelTrigger
{
    /**
     * Trigger name
     */
    public static function name(): string
    {
        return __('documents::document.workflows.triggers.status_changed');
    }

    /**
     * The trigger related model
     */
    public static function model(): string
    {
        return \Modules\Documents\App\Models\Document::class;
    }

    /**
     * The field to track changes on
     */
    public static function field(): string
    {
        return 'status';
    }

    /**
     * Provide the change values the user to choose from
     *
     * @return \Modules\Core\App\Fields\Select
     */
    public static function changeField()
    {
        return Select::make('document_status')->options([
            DocumentStatus::DRAFT->name => DocumentStatus::DRAFT->displayName(),
            DocumentStatus::SENT->name => DocumentStatus::SENT->displayName(),
            DocumentStatus::ACCEPTED->name => DocumentStatus::ACCEPTED->displayName(),
            DocumentStatus::LOST->name => DocumentStatus::LOST->displayName(),
        ]);
    }

    /**
     * Trigger available actions
     */
    public function actions(): array
    {
        return [
            (new SendEmailAction)->toResources(ResourcesSendEmailToField::make()->options([
                'contacts' => [
                    'label' => __('documents::document.workflows.actions.fields.email_to_contact'),
                    'resource' => 'contacts',
                ],
                'companies' => [
                    'label' => __('documents::document.workflows.actions.fields.email_to_company'),
                    'resource' => 'companies',
                ],
                'user' => [
                    'label' => __('documents::document.workflows.actions.fields.email_to_owner_email'),
                    'resource' => 'users',
                ],
                'creator' => [
                    'label' => __('documents::document.workflows.actions.fields.email_to_creator_email'),
                    'resource' => 'users',
                ],
            ])),
            new WebhookAction,
            new MarkAssociatedDealsAsWon('documents'),
            new MarkAssociatedDealsAsLost('documents'),
        ];
    }
}
