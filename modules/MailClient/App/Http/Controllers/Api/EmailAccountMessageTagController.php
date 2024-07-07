<?php
 

namespace Modules\MailClient\App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\App\Http\Controllers\ApiController;
use Modules\MailClient\App\Http\Resources\EmailAccountMessageResource;
use Modules\MailClient\App\Models\EmailAccountMessage;

class EmailAccountMessageTagController extends ApiController
{
    /**
     * Sync tags for the given message.
     */
    public function __invoke(string $messageId, Request $request): JsonResponse
    {
        $message = EmailAccountMessage::find($messageId);

        $this->authorize('update', $message);

        $message->syncTagsWithType($request->input('tags', []), EmailAccountMessage::TAGS_TYPE);
        $message->load('tags');

        return $this->response(new EmailAccountMessageResource(
            $message
        ));
    }
}
