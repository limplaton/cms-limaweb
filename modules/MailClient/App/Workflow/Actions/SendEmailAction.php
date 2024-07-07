<?php
 

namespace Modules\MailClient\App\Workflow\Actions;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Arr;
use Modules\Core\App\Common\Placeholders\Placeholders as BasePlaceholders;
use Modules\Core\App\Contracts\Presentable;
use Modules\Core\App\Contracts\Resources\HasEmail;
use Modules\Core\App\Facades\Innoclapps;
use Modules\Core\App\Fields\Email;
use Modules\Core\App\Fields\Select;
use Modules\Core\App\Fields\Text;
use Modules\Core\App\MailableTemplate\Renderer;
use Modules\Core\App\Resource\PlaceholdersGroup;
use Modules\Core\App\Resource\ResourcePlaceholders;
use Modules\Core\App\Workflow\Action;
use Modules\MailClient\App\Client\Compose\Message;
use Modules\MailClient\App\Client\Exceptions\ConnectionErrorException;
use Modules\MailClient\App\Concerns\InteractsWithEmailMessageAssociations;
use Modules\MailClient\App\Criteria\EmailAccountsForUserCriteria;
use Modules\MailClient\App\Fields\MailEditor;
use Modules\MailClient\App\Models\EmailAccount;
use Modules\MailClient\App\Services\EmailAccountMessageSyncService;
use Modules\Users\App\Models\User;
use ReflectionClass;

class SendEmailAction extends Action implements ShouldQueue
{
    use InteractsWithEmailMessageAssociations;

    /**
     * Available mail template merge fields
     *
     * @var callable|null
     */
    public $placeholders;

    /**
     * Special resources To field
     *
     * @var null|\Modules\MailClient\App\Workflow\Actions\ResourcesSendEmailToField
     */
    public $resourcesToField;

    /**
     * Fields provider callback
     *
     * @var callable|null
     */
    public $fieldsCallback;

    /**
     * Run the trigger
     *
     * @return void
     */
    public function run()
    {
        $account = $this->getAccountForSending();

        if (! $account || ! $account->canSendEmail()) {
            return;
        }

        try {
            if ($address = $this->getEmailAddress()) {

                $composer = new Message($account->createClient(), $account->sentFolder->identifier());

                [$subject, $message] = $this->parsePlaceholders();

                $message = tap($composer, function ($instance) use ($address, $message, $subject) {
                    $instance->htmlBody($message)
                        ->subject($subject)
                        ->to($address)
                        ->withTrackers();

                    if ($resources = $this->getAssociateableResources()) {
                        $this->addComposerAssociationsHeaders($instance, $resources);
                    }
                })->send();

                if ($message) {
                    (new EmailAccountMessageSyncService)->create(
                        $account->id,
                        $message,
                        $this->getAssociateableResources() ?? []
                    );
                }
            }
        } catch (ConnectionErrorException) {
            $account->setAuthRequired();
        }
    }

    /**
     * Get the account that is intended to be used to send the mail.
     */
    protected function getAccountForSending(): ?EmailAccount
    {
        if ($this->email_account_id === 'owner_account') {
            if (! $this->model->user) {
                return null;
            }

            $accounts = $this->getEmailAccountsForUser($this->model->user);

            return $accounts->first(
                fn (EmailAccount $account) => $account->isPrimary($this->model->user)
            ) ?? $accounts->first();
        }

        return EmailAccount::find($this->email_account_id);
    }

    /**
     * Parse message body placeholders for the current email
     */
    protected function parsePlaceholders(): array
    {
        if ($this->resourcesToField) {
            return $this->parsePlaceholdersWhenToField();
        }

        return with($this->getMailRenderer(), function ($renderer) {
            return [$renderer->renderSubject(), $renderer->renderHtmlLayout()];
        });
    }

    /**
     * Get the message for the mail to be sent.
     */
    protected function getMessage(): string
    {
        $message = $this->message;

        if (! $message) {
            return '';
        }

        $signature = null;

        if ($this->email_account_id === 'owner_account') {
            $signature = $this->model->user->mail_signature;
        } elseif ($account = $this->getAccountForSending()) {
            if ($account->isPersonal()) {
                $signature = $account->user->mail_signature;
            }
        }

        // TODO, what about shared?

        if ($signature) {
            $message = $message.'<br /><br />----------<br />'.$signature;
        }

        return $message;
    }

    /**
     * Parse the message for sending when via resource to field
     */
    protected function parsePlaceholdersWhenToField(): array
    {
        $message = $this->getMessage();
        $subject = $this->subject;

        $groups = [];

        foreach ($this->getResourcesForPlaceholders() as $resourceName => $ids) {
            // At this time, only from the for associateable the placeholders are taken
            if (count($ids) > 0 && $resource = Innoclapps::resourceByName($resourceName)) {
                $groups[$resourceName] = new PlaceholdersGroup($resource, $resource->displayQuery()->find($ids[0]));
            }
        }

        $placeholders = new ResourcePlaceholders(array_values($groups));

        return [
            $placeholders->render($subject),
            BasePlaceholders::cleanup($placeholders->parseWhenViaInputFields($message)),
        ];
    }

    /**
     * Get email accounts that the given user can see.
     */
    protected function getEmailAccountsForUser(User $user)
    {
        return EmailAccount::criteria(new EmailAccountsForUserCriteria($user))->get();
    }

    /**
     * Action available fields
     */
    public function fields(): array
    {
        $accounts = $this->getEmailAccountsForUser(auth()->user());

        $fields = [
            Select::make('email_account_id')->options(function () use ($accounts) {
                return $accounts->mapWithKeys(function (EmailAccount $account) {
                    return [$account->id => $account->email];
                })->union([
                    'owner_account' => __('mailclient::mail.workflows.fields.send_from_owner_primary_account'),
                ]);
            })
                ->withMeta(['attributes' => ['placeholder' => __('mailclient::mail.workflows.fields.from_account')]])
                ->rules(['required', 'in:'.$accounts->pluck('id')->push('owner_account')->implode(',')]),

            Text::make('subject')
                ->withMeta(['attributes' => ['placeholder' => __('mailclient::mail.workflows.fields.subject')]])
                ->rules('required'),

            MailEditor::make('message')
                ->withMeta(['attributes' => array_merge(
                    [
                        'placeholder' => __('mailclient::mail.workflows.fields.message'),
                    ],
                    $this->resourcesToField ? [
                        'placeholders' => ResourcePlaceholders::createGroupsFromResources(
                            $this->filteredResourcesForPlaceholders()->all()
                        ),
                        'placeholders-disabled' => true,
                    ] : []
                ),
                ])
                ->rules('required'),
        ];

        if ($this->fieldsCallback) {
            foreach (array_reverse(Arr::wrap(call_user_func($this->fieldsCallback))) as $field) {
                array_unshift($fields, $field);
            }
        }

        array_unshift($fields, is_null($this->resourcesToField) ? $this->regularToField() : $this->resourcesToField);

        return $fields;
    }

    /**
     * Create regular To field
     *
     * @return \Modules\Core\App\Fields\Email
     */
    protected function regularToField()
    {
        return Email::make('to')
            ->withMeta(['attributes' => ['placeholder' => __('mailclient::mail.workflows.fields.to')]])
            ->rules('required');
    }

    /**
     * Filtered resources from the TO field for placeholders
     *
     * @return \Illuminate\Support\Collection
     */
    protected function filteredResourcesForPlaceholders()
    {
        $resourceFromToField = array_keys($this->resourcesToField->getToResources());

        // Add the main model as resource from the TO field as it's resource as well
        // for the placeholders e.q. deal created => send email to contacts
        // the resources are the deals resource and the contacts resource
        if ($this->viaModelTrigger() && $modelResource = Innoclapps::resourceByModel($this->trigger()->model())) {
            $resourceFromToField[] = $modelResource->name();
        }

        // Filter non existent
        $nonExistent = array_diff($resourceFromToField, Innoclapps::getResourcesNames());

        // Resources that exists
        return collect(array_unique(array_diff($resourceFromToField, $nonExistent)));
    }

    /**
     * Get the email address(s) that email should be sent to
     *
     * @return mixed
     */
    protected function getEmailAddress()
    {
        if (is_null($this->resourcesToField)) {
            return $this->to; // regular email
        }

        return $this->recipientsWhenResourcesToField($this->model);
    }

    /**
     * Get the message associateable resources
     */
    protected function getAssociateableResources(): ?array
    {
        if (! $this->resourcesToField || ! $this->viaModelTrigger()) {
            return null;
        }

        // Primary model that the trigger was triggered for
        $associations = [
            $this->model::resource()->name() => [$this->model->getKey()],
        ];

        // Next we will check if there are associations available
        // to associate the actual message to other resources
        // e.q. deal created, send email to primary contact in this case
        // the associations to the message will be the deal and the first primary contact
        $resource = $this->resourcesToField->getToResources()[$this->to];

        // The actual resource model is User, we don't associate
        // as the user is not associateable or if the TO is the same as the resource e.q. contact send to contact email
        // as the contact is already associated above
        if ($resource::$model === User::class || $resource::$model === $this->model::class) {
            return $associations;
        }

        return array_merge($this->createPrimaryRecordFromResource($resource));
    }

    /**
     * Get resources available for placeholders
     */
    protected function getResourcesForPlaceholders(): array
    {
        if (! $this->resourcesToField || ! $this->viaModelTrigger()) {
            return [];
        }

        $resources = [];

        // The resource the trigger was triggered for
        // The primary resources is also included in the filtered resources for placeholders
        $primaryResource = $this->model->resource();

        foreach ($this->filteredResourcesForPlaceholders() as $resourceName) {
            if ($primaryResource->name() === $resourceName) {
                $resources[$primaryResource->name()] = [$this->model->getKey()];
            } else {
                $resources = array_merge_recursive(
                    $resources,
                    $this->createPrimaryRecordFromResource(
                        $this->resourcesToField->getToResources()[$resourceName]
                    ),
                );
            }
        }

        return $resources;
    }

    /**
     * Create primary associations array from the given resource
     *
     * @param  \Modules\Core\App\Resource\Resource  $resource
     * @return array
     */
    protected function createPrimaryRecordFromResource($resource)
    {
        // Only first resource record, the primary one
        return [
            $resource->name() => array_filter([
                $this->model->{$resource->associateableName()}->first()?->getKey(),
            ]),
        ];
    }

    /**
     * Determine the To email addresses
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return array|null
     */
    protected function recipientsWhenResourcesToField($model)
    {
        $resource = $this->resourcesToField->getToResources()[$this->to];

        // Same resource, e.q. company send email to company email
        if ($resource::$model === $this->model::class) { // or $this->to === 'self'
            if ($model instanceof User) {
                return $this->getEmailAddressWhenUserModel($model);
            } elseif (! $model::resource() instanceof HasEmail
                || ! $address = $model->{$model::resource()->emailAddressField()}) {
                return;
            }

            return $this->createAddress($address, $model);
        } elseif ($resource::$model === User::class) {
            // When using User, the To must be the relation name e.q. creator or user or any other relation name
            return $this->getEmailAddressWhenUserModel($this->model->{$this->to});
        }

        return $this->extractPrimaryResourceRecordEmailAddress($this->model->{$this->to}) ?: null;
    }

    /**
     * Get email addresses when user model
     *
     * @param  \Modules\Users\App\Models\User|null  $user
     */
    protected function getEmailAddressWhenUserModel($user): ?array
    {
        if (! $user) {
            return null;
        }

        return $this->createAddress($user->email, $user->name);
    }

    /**
     * Get email address from resource associations/resources from the primary record
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $models
     */
    protected function extractPrimaryResourceRecordEmailAddress($models): array
    {
        if ($models->isEmpty()) {
            return [];
        }

        // The first one is the primary record
        return with($models->first(), function ($primary) {
            if (! $primary ||
                ! $primary->resource() instanceof HasEmail ||
                ! $address = $primary->{$primary::resource()->emailAddressField()}) {
                return [];
            }

            return $this->createAddress($address, $primary);
        });
    }

    /**
     * Add To for for resources.
     */
    public function toResources(ResourcesSendEmailToField $field): static
    {
        $this->resourcesToField = $field;

        return $this;
    }

    /**
     * Add fields to the send email action
     */
    public function withFields(callable $callback): static
    {
        $this->fieldsCallback = $callback;

        return $this;
    }

    /**
     * Add available mail template placeholders.
     */
    public function withPlaceholders(callable $callback): static
    {
        $this->placeholders = $callback;

        return $this;
    }

    /**
     * Get the action placeholders when is not via resourceToField
     */
    public function placeholders(): ?BasePlaceholders
    {
        if (! $this->placeholders) {
            return null;
        }

        if (is_callable($this->placeholders)) {
            $placeholders = call_user_func($this->placeholders, $this);
        } else {
            // When waking up from serialization
            $placeholders = $this->placeholders;
        }

        return $placeholders instanceof BasePlaceholders ? $placeholders : new BasePlaceholders($placeholders);
    }

    /**
     * Create address for the mailer
     *
     * @param  string  $address
     * @param  \Illuminate\Database\Eloquent\Model|string  $name
     * @return array
     */
    protected function createAddress($address, $name)
    {
        return [
            'address' => $address,
            'name' => $name instanceof Presentable ? $name->displayName() : $name,
        ];
    }

    /**
     * Get the mail content rendered
     */
    protected function getMailRenderer(): Renderer
    {
        return app(Renderer::class, [
            'htmlTemplate' => $this->getMessage(),
            'subject' => $this->subject,
            'placeholders' => $this->placeholders(),
        ]);
    }

    /**
     * Action name
     */
    public static function name(): string
    {
        return __('mailclient::mail.workflows.actions.send');
    }

    /**
     * jsonSerialize
     */
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'placeholders' => $this->placeholders(),
        ]);
    }

    /**
     * Prepare the instance for serialization.
     *
     * @return array
     */
    public function __sleep()
    {
        $exclude = ['placeholders', 'fieldsCallback', 'resourcesToField'];

        $properties = (new ReflectionClass($this))->getProperties();

        return array_values(array_filter(array_map(function ($p) use ($exclude) {
            return ($p->isStatic() || in_array($name = $p->getName(), $exclude)) ? null : $name;
        }, $properties)));
    }

    /**
     * Wake up the instance from serialization
     *
     * @return array
     */
    public function __wakeup()
    {
        // Get the defined original action to replace data
        $action = $this->trigger()->getAction($this);

        $this->resourcesToField = $action->resourcesToField;
        $this->fieldsCallback = $action->fieldsCallback;
        $this->placeholders = $action->setData($this->data)->placeholders();
    }
}
