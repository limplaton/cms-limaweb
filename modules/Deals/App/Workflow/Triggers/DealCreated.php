<?php
 

namespace Modules\Deals\App\Workflow\Triggers;

use Modules\Activities\App\Workflow\Actions\CreateActivityAction;
use Modules\Core\App\Contracts\Workflow\EventTrigger;
use Modules\Core\App\Contracts\Workflow\ModelTrigger;
use Modules\Core\App\Workflow\Actions\WebhookAction;
use Modules\Core\App\Workflow\Trigger;
use Modules\MailClient\App\Workflow\Actions\ResourcesSendEmailToField;
use Modules\MailClient\App\Workflow\Actions\SendEmailAction;

class DealCreated extends Trigger implements EventTrigger, ModelTrigger
{
    /**
     * Trigger name
     */
    public static function name(): string
    {
        return __('deals::deal.workflows.triggers.created');
    }

    /**
     * The trigger related model
     */
    public static function model(): string
    {
        return \Modules\Deals\App\Models\Deal::class;
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
                'contacts' => [
                    'label' => __('deals::deal.workflows.actions.fields.email_to_contact'),
                    'resource' => 'contacts',
                ],
                'companies' => [
                    'label' => __('deals::deal.workflows.actions.fields.email_to_company'),
                    'resource' => 'companies',
                ],
                'user' => [
                    'label' => __('deals::deal.workflows.actions.fields.email_to_owner_email'),
                    'resource' => 'users',
                ],
                'creator' => [
                    'label' => __('deals::deal.workflows.actions.fields.email_to_creator_email'),
                    'resource' => 'users',
                ],
            ])),
            new WebhookAction,
        ];
    }
}
