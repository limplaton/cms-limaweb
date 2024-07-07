<?php
 

namespace Modules\Documents\App\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Modules\Documents\App\Enums\DocumentStatus;

/** @mixin \Modules\Core\App\Models\Model */
trait HasDocuments
{
    /**
     * Get all of the associated documents for the contact.
     */
    public function documents(): MorphToMany
    {
        return $this->morphToMany(\Modules\Documents\App\Models\Document::class, 'documentable');
    }

    /**
     * Get the draft documents the user is authorized to see
     */
    public function draftDocuments(): MorphToMany
    {
        return $this->documents()->where('status', DocumentStatus::DRAFT);
    }
}
