<?php
 

namespace Modules\Core\App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MediaUploader;
use Modules\Core\App\Facades\Innoclapps;
use Modules\Core\App\Http\Controllers\ApiController;
use Modules\Core\App\Http\Resources\MediaResource;
use Modules\Core\App\Models\PendingMedia;
use Plank\Mediable\Exceptions\MediaUploadException;
use Plank\Mediable\HandlesMediaUploadExceptions;

class PendingMediaController extends ApiController
{
    use HandlesMediaUploadExceptions;

    /**
     * Upload pending media.
     */
    public function store(string $draftId, Request $request): JsonResponse
    {
        try {
            $media = MediaUploader::fromSource($request->file('file'))
                ->toDirectory('pending-attachments')
                ->setAllowedExtensions(Innoclapps::allowedUploadExtensions())
                ->upload();

            $media->markAsPending($draftId);
        } catch (MediaUploadException $e) {
            /** @var \Symfony\Component\HttpKernel\Exception\HttpException */
            $exception = $this->transformMediaUploadException($e);

            return $this->response(['message' => $exception->getMessage()], $exception->getStatusCode());
        }

        return $this->response(new MediaResource($media->load('pendingData')), JsonResponse::HTTP_CREATED);
    }

    /**
     * Delete pending media attachment.
     */
    public function destroy(string $pendingMediaId): JsonResponse
    {
        PendingMedia::findOrFail($pendingMediaId)->purge();

        return $this->response('', JsonResponse::HTTP_NO_CONTENT);
    }
}
