<?php
 

namespace Modules\Contacts\App\Workflow\Triggers;

use Modules\Activities\App\Workflow\Actions\CreateActivityAction;
use Modules\Core\App\Contracts\Workflow\EventTrigger;
use Modules\Core\App\Contracts\Workflow\ModelTrigger;
use Modules\Core\App\Workflow\Actions\WebhookAction;
use Modules\Core\App\Workflow\Trigger;
use Modules\MailClient\App\Workflow\Actions\ResourcesSendEmailToField;
use Modules\MailClient\App\Workflow\Actions\SendEmailAction;

class CompanyCreated extends Trigger implements EventTrigger, ModelTrigger
{
    /**
     * Trigger name
     */
    public static function name(): string
    {
        return __('contacts::company.workflows.triggers.created');
    }

    /**
     * The trigger related model
     */
    public static function model(): string
    {
        return \Modules\Contacts\App\Models\Company::class;
    }

    /**
     * The model event trigger
     */
    public static function event(): string
    {
        return 'created';
    }

    /**
     * Trigger available actions
     */
    public function actions(): array
    {
        return [
            new CreateActivityAction,
            (new SendEmailAction)->toResources(ResourcesSendEmailToField::make()->options([
                'self' => [
                    'label' => __('contacts::company.workflows.actions.fields.email_to_company'),
                    'resource' => 'companies',
                ],
                'user' => [
                    'label' => __('contacts::company.workflows.actions.fields.email_to_owner_email'),
                    'resource' => 'users',
                ],
                'creator' => [
                    'label' => __('contacts::company.workflows.actions.fields.email_to_creator_email'),
                    'resource' => 'users',
                ],
                'contacts' => [
                    'label' => __('contacts::company.workflows.actions.fields.email_to_contact'),
                    'resource' => 'contacts',
                ],
            ])),
            new WebhookAction,
        ];
    }
}
