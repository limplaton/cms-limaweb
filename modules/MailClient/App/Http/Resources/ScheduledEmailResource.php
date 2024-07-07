<?php
 

namespace Modules\MailClient\App\Http\Resources;

use Illuminate\Http\Request;
use Modules\Core\App\Http\Resources\JsonResource;
use Modules\Users\App\Http\Resources\UserResource;

/** @mixin \Modules\MailClient\App\Models\ScheduledEmail */
class ScheduledEmailResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): array
    {
        return $this->withCommonData([
            'subject' => $this->subject,
            'html_body' => $this->html_body,
            'to' => $this->to,
            'type' => $this->type,
            'email_account_id' => $this->email_account_id,
            'scheduled_at' => $this->scheduled_at,
            'user_id' => $this->user_id,
            'status' => $this->status,
            'sent_at' => $this->sent_at,
            'fail_reason' => $this->fail_reason,
            'retry_after' => $this->retry_after,
            'user' => new UserResource($this->whenLoaded('user')),
        ], $request);
    }
}
