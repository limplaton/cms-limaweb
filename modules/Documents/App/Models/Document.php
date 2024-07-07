<?php
 

namespace Modules\Documents\App\Models;

use Barryvdh\DomPDF\Facade\Pdf;
use CyrildeWit\EloquentViewable\Contracts\Viewable;
use CyrildeWit\EloquentViewable\InteractsWithViews;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\Billable\App\Concerns\HasProducts;
use Modules\Billable\App\Services\BillableService;
use Modules\Core\App\Common\Changelog\LogsModelChanges;
use Modules\Core\App\Common\Media\HasMedia;
use Modules\Core\App\Common\Placeholders\DatePlaceholder;
use Modules\Core\App\Common\Placeholders\GenericPlaceholder;
use Modules\Core\App\Common\Placeholders\Placeholders as BasePlaceholders;
use Modules\Core\App\Common\Timeline\Timelineable;
use Modules\Core\App\Concerns\HasCreator;
use Modules\Core\App\Concerns\HasUuid;
use Modules\Core\App\Concerns\LazyTouchesViaPivot;
use Modules\Core\App\Concerns\Prunable;
use Modules\Core\App\Contracts\Presentable;
use Modules\Core\App\Facades\ChangeLogger;
use Modules\Core\App\Models\Model;
use Modules\Core\App\Resource\Resource;
use Modules\Core\App\Resource\Resourceable;
use Modules\Core\App\Resource\ResourcePlaceholders;
use Modules\Core\App\Support\Carbon;
use Modules\Core\App\Workflow\HasWorkflowTriggers;
use Modules\Documents\App\Content\DocumentContent;
use Modules\Documents\App\Content\FontsExtractor;
use Modules\Documents\App\Enums\DocumentStatus;
use Modules\Documents\App\Enums\DocumentViewType;
use Modules\Documents\App\Mail\SendDocument;
use Modules\Documents\Database\Factories\DocumentFactory;
use Modules\Users\App\Models\User;

/**
 * @property-read \Modules\Documents\App\Content\DocumentContent $content
 */
class Document extends Model implements Presentable, Viewable
{
    use HasCreator,
        HasFactory,
        HasMedia,
        HasProducts,
        HasUuid,
        HasWorkflowTriggers,
        InteractsWithViews,
        LazyTouchesViaPivot,
        LogsModelChanges,
        Prunable,
        Resourceable,
        SoftDeletes,
        Timelineable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'requires_signature',
        'content',
        'data',
        'brand_id',
        'user_id',
        'document_type_id',
        'send_at',
        'view_type',
        'locale',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'user_id' => 'int',
        'created_by' => 'int',
        'marked_accepted_by' => 'int',
        'document_type_id' => 'int',
        'brand_id' => 'int',
        'owner_assigned_date' => 'datetime',
        'accepted_at' => 'datetime',
        'original_date_sent' => 'datetime',
        'last_date_sent' => 'datetime',
        'send_at' => 'datetime',
        'requires_signature' => 'bool',
        'status' => DocumentStatus::class,
        'data' => 'array',
        'view_type' => DocumentViewType::class,
    ];

    /**
     * Add new activity
     */
    public function addActivity(array $data): static
    {
        ChangeLogger::onModel($this, $data)->log();

        return $this;
    }

    /**
     * Get the document signers
     */
    public function signers(): HasMany
    {
        return $this->hasMany(\Modules\Documents\App\Models\DocumentSigner::class);
    }

    /**
     * A document belongs to type
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(\Modules\Documents\App\Models\DocumentType::class, 'document_type_id');
    }

    /**
     * Get all of the deals that are associated with this document.
     */
    public function deals(): MorphToMany
    {
        return $this->morphedByMany(\Modules\Deals\App\Models\Deal::class, 'documentable');
    }

    /**
     * Get all of the contacts that are associated with this document.
     */
    public function contacts(): MorphToMany
    {
        return $this->morphedByMany(\Modules\Contacts\App\Models\Contact::class, 'documentable')
            ->withTimestamps()
            ->orderBy('documentables.created_at');
    }

    /**
     * Get all of the companies that are associated with this document.
     */
    public function companies(): MorphToMany
    {
        return $this->morphedByMany(\Modules\Contacts\App\Models\Company::class, 'documentable')
            ->withTimestamps()
            ->orderBy('documentables.created_at');
    }

    /**
     * Get the document owner
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\Modules\Users\App\Models\User::class);
    }

    /**
     * Get the document brand
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(\Modules\Brands\App\Models\Brand::class);
    }

    /**
     * Get the document PDF font.
     */
    public function pdfFont(): array
    {
        $family = $this->data['pdf']['font'] ?? null;

        if (! $family) {
            return $this->brand->pdfFont();
        }

        $fontsExtractor = new FontsExtractor;
        $fonts = $fontsExtractor->getFontsFromConfig();

        $font = array_merge($fonts->where('font-family', $family)->first(), [
            'name' => trim(explode(',', $family)[0]),
        ]);

        return $font;
    }

    /**
     * Get the document PDF size.
     */
    public function pdfSize()
    {
        return $this->data['pdf']['size'] ?? $this->brand->config['pdf']['size'] ?? 'a4';
    }

    /**
     * Get the document PDF orientation.
     */
    public function pdfOrientation()
    {
        return $this->data['pdf']['orientation'] ?? $this->brand->config['pdf']['orientation'] ?? 'landscape';
    }

    /**
     * Check whether all signers has signed the document
     */
    public function everyoneSigned(): bool
    {
        return $this->signers->filter->missingSignature()->isEmpty();
    }

    /**
     * Check whether at least one signer signed the document
     */
    public function atLeastOneSigned(): bool
    {
        return $this->signers->filter->hasSignature()->count() > 0;
    }

    /**
     * Get the model display name
     */
    public function displayName(): string
    {
        return $this->title;
    }

    /**
     * Get the URL path
     */
    public function path(): string
    {
        return "/documents/{$this->id}";
    }

    /**
     * Get the document public URL
     */
    public function publicUrl(): Attribute
    {
        return Attribute::get(
            fn () => route('document.public', $this->uuid)
        );
    }

    /**
     * Get the document content
     */
    public function content(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => new DocumentContent($value, $this),
        );
    }

    /**
     * Provide the total column to be updated whenever the billable is updated
     */
    public function totalColumn(): string
    {
        return 'amount';
    }

    /**
     * Get the attributes that may contain pending media
     */
    public function textAttributesWithMedia(): string
    {
        return 'content';
    }

    /**
     * Get the document available placeholders
     */
    public function placeholders(): BasePlaceholders
    {
        $default = new BasePlaceholders([
            GenericPlaceholder::make('company_name', fn () => config('app.name')),
            GenericPlaceholder::make('brand.display_name', fn () => $this->brand->display_name),
            GenericPlaceholder::make('brand.internal_name', fn () => $this->brand->name),
            GenericPlaceholder::make('document.title', fn () => $this->title)
                ->description(__('documents::document.document').' - '.__('documents::document.title')),
            GenericPlaceholder::make('document.type', fn () => $this->type->name)
                ->description(__('documents::document.document').' - '.__('documents::document.type.type')),
            GenericPlaceholder::make('document.owner', fn () => $this->user->name)
                ->description(__('documents::document.document').' - '.__('documents::fields.documents.user.name')),
            GenericPlaceholder::make('document.owner_email_address', fn () => $this->user->email)
                ->description(__('documents::document.document').' - '.__('core::app.owner_email_address')),
            DatePlaceholder::make('document.created_at', fn () => $this->created_at)
                ->description(__('documents::document.document').' - '.__('core::app.created_at')),
        ]);

        return $this->addPlaceholdersFromResources($default);
    }

    /**
     * Add placeholders from the resources
     */
    protected function addPlaceholdersFromResources(BasePlaceholders $placeholders): BasePlaceholders
    {
        foreach (static::resource()->associateableResources() as $relation => $resource) {
            // Placeholders for first associated (primary record)
            $model = $this->exists ?
                $this->{$relation}()->select($resource->newModel()->getKeyName())->first() :
                null;

            // When the placeholders are intended to be displayed, we will check if there is associated
            // model to use the merge fields to, if no model, no need to to add the fields
            if ($this->exists && ! $model) {
                continue;
            } elseif ($model) {
                // Re-query for display to eager load all the data
                $model = $resource->displayQuery()->find($model->getKey());
            }

            $placeholders->push(...collect(ResourcePlaceholders::make($resource, $model)->all())
                ->each(function ($placeholder) use ($resource) {
                    $placeholder->description($resource->singularLabel().' - '.$placeholder->description);
                })->all());
        }

        return $placeholders;
    }

    /**
     * Get the document PDF filename.
     */
    public function pdfFilename(): string
    {
        return (Str::slug($this->type->name) ?: $this->type->name).'.pdf';
    }

    /**
     * Get the document PDF instance
     *
     * @return \Barryvdh\DomPDF\PDF
     */
    public function pdf()
    {
        return Pdf::loadView('documents::pdf', ['document' => $this])
            ->setPaper($this->pdfSize(), $this->pdfOrientation())
            ->setWarnings(false);
    }

    /**
     * Scope a query to include only documents due for sending.
     */
    public function scopeDueForSending(Builder $query): void
    {
        $query->whereNotNull('send_at')->where('send_at', '<=', Carbon::asAppTimezone());
    }

    /**
     * Get the timeline component for front-end
     */
    public function getTimelineComponent(): string
    {
        return 'record-tab-timeline-document';
    }

    /**
     * Mark the given document as lost
     */
    public function markAsLost(User $user): self|bool
    {
        if ($this->status === DocumentStatus::LOST || $this->status === DocumentStatus::ACCEPTED) {
            return false;
        }

        $this->status = DocumentStatus::LOST;
        $this->save();

        $this->addActivity([
            'lang' => [
                'key' => 'documents::document.activity.marked_as_lost',
                'attrs' => [
                    'user' => $user->name,
                ],
            ],
        ]);

        return $this;
    }

    /**
     * Mark the given document as accepted
     */
    public function markAsAccepted(User $user): self|bool
    {
        if ($this->status === DocumentStatus::ACCEPTED) {
            return false;
        }

        $this->forceFill([
            'status' => DocumentStatus::ACCEPTED,
            'accepted_at' => now(),
            'marked_accepted_by' => $user->getKey(),
        ])->save();

        $this->addActivity([
            'type' => 'success',
            'lang' => [
                'key' => 'documents::document.activity.marked_as_accepted',
                'attrs' => [
                    'user' => $user->name,
                ],
            ],
        ]);

        return $this;
    }

    /**
     * Mark the given document as draft
     */
    public function markAsDraft(User $user): self|bool
    {
        if ($this->status !== DocumentStatus::LOST &&
            $this->status === DocumentStatus::ACCEPTED && ! $this->marked_accepted_by) {
            return false;
        }

        $this->forceFill([
            'status' => DocumentStatus::DRAFT,
            'marked_accepted_by' => null,
        ])->save();

        $this->addActivity([
            'lang' => [
                'key' => 'documents::document.activity.marked_as_draft',
                'attrs' => [
                    'user' => $user->name,
                ],
            ],
        ]);

        return $this;
    }

    /**
     * Get localized config from the document brand.
     */
    public function localizedBrandConfig(string $key)
    {
        return $this->brand->getLocalizedConfig($key, $this->locale);
    }

    /**
     * Purge the document data.
     */
    public function purge(): void
    {
        if ($this->billable) {
            $this->billable->delete();
        }

        $this->signers()->delete();
        $this->deals()->withTrashed()->detach();
        $this->contacts()->withTrashed()->detach();
        $this->companies()->withTrashed()->detach();
    }

    /**
     * Eager load the relations that are required for the front end response.
     */
    public function scopeWithCommon(Builder $query): void
    {
        $query->with([
            'brand',
            'type',
            'signers',
            'user',
            'billable',
            'billable.products',
            'billable.products.originalProduct',
            'billable.products.billable',
            'changelog',
        ]);
    }

    /**
     * Clone the document.
     */
    public function clone(int $userId): Document
    {
        $newDocument = $this->replicate([
            'uuid', 'accepted_at', 'marked_accepted_by', 'send_at', 'original_date_sent',
            'last_date_sent', 'sent_by', 'approved_by', 'approved_date', 'approval_feedback', 'user_id',
            'owner_assigned_date', 'created_by', 'data',
        ]);

        $newDocument->forceFill([
            'status' => DocumentStatus::DRAFT,
            'data' => array_merge($this->data ?? [], [
                'send_initiated_by' => null,
                'recipients' => collect($this->data['recipients'] ?? [])
                    ->map(function (array $recipient) {
                        Arr::forget($recipient, 'sent_at');

                        return $recipient;
                    }),
            ]),
            'title' => clone_prefix($this->title),
            'user_id' => $userId,
            'created_by' => $userId,
            'owner_assigned_date' => now(),
        ])->save();

        $newDocument->signers()->createMany(
            $this->signers->map(fn ($signer) => $signer->only(['name', 'email', 'send_email']))
        );

        if ($this->billable) {
            (new BillableService())->save([
                'tax_type' => $this->billable->tax_type,
                'products' => $this->billable->products->map(function ($product) {
                    return $product->only($product->formAttributes());
                })->all(),
            ], $newDocument);
        }

        $this->resource()->associateableResources()->each(function (Resource $resource, string $relation) use ($newDocument) {
            $newDocument->{$relation}()->attach($this->{$relation}->modelKeys());
        });

        return $newDocument;
    }

    /**
     * Send the document to it's recipients.
     */
    public function send(): static
    {
        $recipients = array_merge($this->sendMailToRecipients(), $this->sendMailToSigners());
        $sender = User::find($this->data['send_initiated_by']);

        $this->forceFill(['last_date_sent' => now()]);

        if (! $this->original_date_sent && ! $this->sent_by) {
            $this->forceFill([
                'original_date_sent' => now(),
                'sent_by' => $sender->getKey(),
            ]);
        }

        if ($this->status === DocumentStatus::DRAFT) {
            $this->forceFill(['status' => DocumentStatus::SENT]);
        }

        $this->save();

        if (count($recipients) > 0) {
            $this->logDocumentSentActivity($recipients, $sender);
        }

        return $this;
    }

    /**
     * Send mail to the document signers.
     */
    protected function sendMailToSigners(): array
    {
        $sentTo = [];

        $this
            ->signers
            ->filter(
                fn ($signer) => $signer['send_email'] ?? false === true
            )
            ->whenNotEmpty(function ($recipients) {
                Mail::to($recipients)->send(new SendDocument($this));
            })->each(function (DocumentSigner $signer) use (&$sentTo) {
                $this->signers()->updateOrCreate(['email' => $signer->email], [
                    'sent_at' => now(),
                    'send_email' => false,
                ]);

                $sentTo[] = $signer;
            });

        return $sentTo;
    }

    /**
     * Send mail to the document recipients.
     */
    protected function sendMailToRecipients(): array
    {
        $sentTo = [];

        collect($this->data['recipients'] ?? [])
            ->filter(
                fn ($recipient) => $recipient['send_email'] ?? false === true
            )
            ->whenNotEmpty(function ($recipients) {
                Mail::to($recipients)->send(new SendDocument($this));
            })->each(function ($recipient, $key) use (&$sentTo) {
                $this->data = array_merge($this->data, [
                    'recipients' => with($this->data['recipients'], function ($recipients) use ($key) {
                        $recipients[$key]['sent_at'] = now();
                        $recipients[$key]['send_email'] = false;

                        return $recipients;
                    }),
                ]);

                $sentTo[] = $recipient;
            });

        return $sentTo;
    }

    /**
     * Log document sent activity.
     */
    protected function logDocumentSentActivity(array $recipients, User $sender): void
    {
        $this->addActivity([
            'lang' => [
                'key' => 'documents::document.activity.sent',
                'attrs' => [
                    'user' => $sender->name,
                ],
            ],
            'section' => [
                'lang' => [
                    'key' => 'documents::document.recipients.recipients',
                ],
                'list' => collect($recipients)->map(function ($recipient) {
                    return [
                        'lang' => [
                            'key' => 'documents::document.activity.sent_recipient',
                            'attrs' => [
                                'name' => $recipient['name'],
                                'email' => $recipient['email'],
                            ],
                        ],
                    ];
                })->all(),
            ],
        ]);
    }

    /**
     * Provide the related pivot relationships to touch.
     */
    protected function relatedPivotRelationsToTouch(): array
    {
        return ['contacts', 'companies', 'deals'];
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): DocumentFactory
    {
        return DocumentFactory::new();
    }
}
