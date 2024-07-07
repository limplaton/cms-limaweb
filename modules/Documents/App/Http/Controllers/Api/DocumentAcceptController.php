<?php
 

namespace Modules\Documents\App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Modules\Core\App\Http\Controllers\ApiController;
use Modules\Core\App\Rules\StringRule;
use Modules\Documents\App\Enums\DocumentStatus;
use Modules\Documents\App\Mail\DocumentSignedThankYouMessage;
use Modules\Documents\App\Models\Document;
use Modules\Documents\App\Notifications\DocumentAccepted;
use Modules\Documents\App\Notifications\SignerSignedDocument;

class DocumentAcceptController extends ApiController
{
    /**
     * Accept a document without signature.
     */
    public function accept(string $uuid): JsonResponse
    {
        $document = Document::where('uuid', $uuid)->firstOrFail();

        if ($document->status === DocumentStatus::ACCEPTED) {
            abort(404, 'This document is already accepted.');
        }

        $document->forceFill([
            'status' => DocumentStatus::ACCEPTED,
            'accepted_at' => now(),
        ])->save();

        $document->addActivity([
            'type' => 'success',
            'lang' => [
                'key' => 'documents::document.activity.accepted',
            ],
        ]);

        $document->user->notify(new DocumentAccepted($document));

        return $this->response('', JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Accept a document with signature.
     */
    public function sign(string $uuid, Request $request): JsonResponse
    {
        $document = Document::where('uuid', $uuid)->firstOrFail();

        if ($document->status === DocumentStatus::ACCEPTED) {
            abort(404, 'This document is already fully signed.');
        }

        $data = $request->validate([
            'email' => ['required', StringRule::make(), 'email'],
            'signature' => ['required', StringRule::make()],
        ]);

        $signature = $data['signature'];

        $signer = $document->signers->where('email', $request->email)->first();

        if (! $signer->hasSignature()) {
            $signer->forceFill([
                'signature' => $signature,
                'sign_ip' => $request->ip(),
                'signed_at' => now(),
            ])->save();

            if ($document->everyoneSigned()) {
                $document->forceFill([
                    'status' => DocumentStatus::ACCEPTED,
                    'accepted_at' => now(),
                ])->save();
            }

            $document->addActivity([
                'lang' => [
                    'key' => 'documents::document.activity.signed',
                    'attrs' => [
                        'signer_name' => $signer->name,
                    ],
                ],
            ]);

            Mail::to($signer->email)->send(new DocumentSignedThankYouMessage($document));

            $document->user->notify(new SignerSignedDocument($document, $signer));
        }

        return $this->response('', JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Validate the document before signing based on the provided email address.
     */
    public function validateEmailAddress(string $uuid, Request $request): JsonResponse
    {
        if ($request->isNotFilled('email')) {
            return $this->response('', JsonResponse::HTTP_NO_CONTENT);
        }

        $document = Document::where('uuid', $uuid)->firstOrFail();

        $signer = $document->signers->where('email', $request->email)->first();

        if (! $signer) {
            return $this->response('', JsonResponse::HTTP_NO_CONTENT);
        }

        return $this->response([
            'name' => $signer->name,
        ]);
    }
}
