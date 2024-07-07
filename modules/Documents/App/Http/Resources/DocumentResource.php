<?php
 

namespace Modules\Documents\App\Http\Resources;

use Illuminate\Http\Request;
use Modules\Billable\App\Http\Resources\BillableResource;
use Modules\Brands\App\Http\Resources\BrandResource;
use Modules\Contacts\App\Http\Resources\CompanyResource;
use Modules\Contacts\App\Http\Resources\ContactResource;
use Modules\Core\App\Http\Resources\ChangelogResource;
use Modules\Core\App\Resource\JsonResource;
use Modules\Deals\App\Http\Resources\DealResource;
use Modules\Users\App\Http\Resources\UserResource;

/** @mixin \Modules\Documents\App\Models\Document */
class DocumentResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Modules\Core\App\Http\Requests\ResourceRequest  $request
     */
    public function toArray(Request $request): array
    {
        return $this->withCommonData([
            'title' => $this->title,
            'document_type_id' => $this->document_type_id,
            'type' => new DocumentTypeResource($this->whenLoaded('type')),
            'status' => $this->status->value,
            'amount' => is_null($this->amount) ? 0 : (float) $this->amount,
            'requires_signature' => $this->requires_signature,
            'content' => clean($this->content),
            'view_type' => $this->view_type,
            'brand_id' => $this->brand_id,
            'brand' => new BrandResource($this->whenLoaded('brand')),
            'send_at' => $this->send_at,
            'original_date_sent' => $this->original_date_sent,
            'last_date_sent' => $this->last_date_sent,
            'accepted_at' => $this->accepted_at,
            'marked_accepted_by' => $this->marked_accepted_by,
            'locale' => $this->locale,
            'created_by' => $this->created_by,

            'user' => new UserResource($this->whenLoaded('user')),
            'user_id' => $this->user_id,
            'owner_assigned_date' => $this->owner_assigned_date,

            'public_url' => $this->when($request->user()->can('view', $this->resource), $this->publicUrl),
            'signers' => DocumentSignerResource::collection($this->whenLoaded('signers')),
            'recipients' => $this->data['recipients'] ?? [],
            'send_mail_account_id' => ($this->data['send_mail_account_id'] ?? null) ? (int) $this->data['send_mail_account_id'] : null,
            'send_mail_subject' => $this->data['send_mail_subject'] ?? null,
            'send_mail_body' => $this->data['send_mail_body'] ?? null,
            'pdf' => $this->data['pdf'] ?? new \stdClass(),
            'google_fonts' => $this->content->usedGoogleFonts(),
            $this->mergeWhen(! $request->isZapier() && $this->userCanViewCurrentResource(), [
                'changelog' => ChangelogResource::collection($this->whenLoaded('changelog')),
                'contacts' => ContactResource::collection($this->whenLoaded('contacts')),
                'companies' => CompanyResource::collection($this->whenLoaded('companies')),
                'deals' => DealResource::collection($this->whenLoaded('deals')),
                'billable' => new BillableResource($this->whenLoaded('billable')),
            ]),
        ], $request);
    }
}
