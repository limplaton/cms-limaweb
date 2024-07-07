<?php
 

namespace Modules\Documents\App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Modules\Billable\App\Services\BillableService;
use Modules\Core\App\Models\Model;
use Modules\Documents\App\Enums\DocumentStatus;
use Modules\Documents\App\Models\Document;
use Modules\Documents\App\Models\DocumentType;
use Modules\Documents\App\Notifications\UserAssignedToDocument;

class DocumentService
{
    /**
     * Create new document in storage.
     */
    public function create(Document $model, array $attributes): Document
    {
        $signers = $attributes['signers'] ?? [];

        $attributes['data'] = collect($this->extraDataAttributes())->mapWithKeys(
            fn ($attribute) => [$attribute => $attributes[$attribute] ?? null]
        )->all();

        // API usage
        if (! ($attributes['document_type_id'] ?? null)) {
            $attributes['document_type_id'] = DocumentType::getDefaultType();
        }

        // API usage when requires_signature is not provided
        if (! ($attributes['requires_signature'] ?? null)) {
            $attributes['requires_signature'] = count($signers) > 0 ? true : false;
        }

        if (! isset($attributes['locale'])) {
            $attributes['locale'] = Auth::user()->preferredLocale();
        }

        $model->fill($attributes);

        $model->owner_assigned_date = now();
        $model->status = DocumentStatus::DRAFT;

        $model->save();

        if ($model->requires_signature === true) {
            $model->signers()->createMany($signers);
        }

        if ($billable = $attributes['billable'] ?? null) {
            (new BillableService())->save($billable, $model);
        }

        if ($model->user_id != Auth::id()) {
            $model->user->notify(new UserAssignedToDocument($model, Auth::user()));
        }

        return $model;
    }

    /**
     * Update document in storage.
     */
    public function update(Model $model, array $attributes): Document
    {
        $originalOwnerId = $model->user_id;

        $signers = Arr::pull($attributes, 'signers');

        if ($model->status === DocumentStatus::ACCEPTED) {
            Arr::forget($attributes, ['content', 'title', 'brand_id', 'requires_signature', 'billable']);
        }

        $data = $model->data ?? [];

        foreach ($this->extraDataAttributes() as $key) {
            if (array_key_exists($key, $attributes)) {
                $data[$key] = $attributes[$key];
            }
        }

        if (($attributes['send'] ?? null) || $model->isDirty('send_at') && $model->send_at) {
            $data['send_initiated_by'] = Auth::id();
        }

        $model->fill([...$attributes, ...['data' => $data]]);

        if ($originalOwnerId != $model->user_id) {
            $model->owner_assigned_date = now();
        }

        $model->save();

        if ($model->requires_signature === false) {
            $model->signers()->delete();
        } elseif (is_array($signers)) {
            $this->updateSigners($signers, $model);
        }

        if ($billable = $attributes['billable'] ?? null) {
            (new BillableService())->save($billable, $model);
        }

        if ($attributes['send'] ?? false) {
            $model->send();
        }

        if ($originalOwnerId != $model->user_id && Auth::id() !== $model->user_id) {
            $model->user->notify(new UserAssignedToDocument($model, Auth::user()));
        }

        return $model;
    }

    /**
     * Save/update the given signers for the given document
     */
    protected function updateSigners(array $signers, Document $document): void
    {
        $signers = collect($signers);
        $altered = false;

        $deleted = $document->signers->pluck('email')->diff($signers->pluck('email'));
        $currentTotal = $document->signers->count();

        if ($currentTotal > 0) {
            if ($deleted->count() === $currentTotal) {
                $altered = (bool) $document->signers()->delete();
            } elseif ($deleted->isNotEmpty()) {
                $altered = (bool) $document->signers()->whereIn('email', $deleted)->delete();
            }

            $signers->each(function (array $signer) use ($document) {
                $this->updateOrCreateSigner($document, $signer['email'], $signer);
            });
        } elseif ($document->status !== DocumentStatus::ACCEPTED) {
            $signers->each(function (array $signer) use ($document) {
                $document->signers()->create($signer);
            });
        }

        if ($altered && $document->status === DocumentStatus::ACCEPTED) {
            // TODO Log activity?
            $document->forceFill([
                'status' => DocumentStatus::DRAFT,
                'accepted_at' => null,
                'marked_accepted_by' => null,
            ])->save();
        }
    }

    /**
     * Update or create document signer.
     */
    protected function updateOrCreateSigner(Document $document, string $email, array $data): void
    {
        $signer = $document->signers()->where('email', $email)->first();

        if ($signer) {
            if ($document->status !== DocumentStatus::ACCEPTED) {
                $signer->fill($data)->save();
            } else {
                $signer->fill(['send_email' => $data['send_email'] ?? false])->save();
            }

            return;
        }

        if ($document->status !== DocumentStatus::ACCEPTED) {
            $document->signers()->create($data);
        }
    }

    /**
     * Get the model extra data attributes
     */
    protected function extraDataAttributes(): array
    {
        return ['send_mail_account_id', 'send_mail_subject', 'send_mail_body', 'recipients', 'pdf'];
    }
}
