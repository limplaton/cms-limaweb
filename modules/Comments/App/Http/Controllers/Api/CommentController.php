<?php
 

namespace Modules\Comments\App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Comments\App\Contracts\HasComments;
use Modules\Comments\App\Contracts\PipesComments;
use Modules\Comments\App\Http\Resources\CommentResource;
use Modules\Comments\App\Models\Comment;
use Modules\Core\App\Facades\Innoclapps;
use Modules\Core\App\Http\Controllers\ApiController;
use Modules\Core\App\Http\Requests\ResourceRequest;
use Modules\Core\App\Resource\Resource;
use Modules\Users\App\Mention\PendingMention;

class CommentController extends ApiController
{
    /**
     * Display the resource comments.
     */
    public function index(ResourceRequest $request): JsonResponse
    {
        $request->validate([
            'via_resource' => [
                'sometimes',
                'required_with:via_resource_id',
                'string',
                Rule::in(Innoclapps::registeredResources()
                    ->whereInstanceOf(PipesComments::class)
                    ->map(fn (Resource $resource) => $resource->name())->all()),
            ],
            'via_resource_id' => [
                'sometimes',
                'numeric',
                'required_with:via_resource',
                Rule::requiredIf(in_array($request->resource()->name(), ['notes', 'calls'])),
            ],
        ]);

        // When the via_resource is not provided, we will validate the actual resource
        // record, otherwise, we will validate the via_resource record e.q. user can see contact
        // and it's calls and a comment is added to the call
        if (! $request->viaResource()) {
            $this->authorize('view', $request->record());
        } else {
            $this->authorize(
                'view',
                $request->findResource($request->via_resource)->newModel()->find($request->via_resource_id)
            );
        }

        return $this->response(
            CommentResource::collection(
                $request->record()->comments()
                    ->with('creator')
                    ->orderBy('created_at')
                    ->get()
            )
        );
    }

    /**
     * Add new resource comment.
     */
    public function store(ResourceRequest $request): JsonResponse
    {
        abort_unless(
            $request->resource() instanceof HasComments,
            404,
            'Comments cannot be added to the provided resource.'
        );

        $input = $request->validate([
            'body' => 'required|string',
            'via_resource' => [
                'sometimes',
                'required_with:via_resource_id',
                'string',
                Rule::in(Innoclapps::registeredResources()
                    ->whereInstanceOf(PipesComments::class)
                    ->map(fn (Resource $resource) => $resource->name())->all()),
            ],
            'via_resource_id' => [
                'sometimes',
                'numeric',
                'required_with:via_resource',
                Rule::requiredIf(in_array($request->resource()->name(), ['notes', 'calls'])),
            ],
        ]);

        // When the via_resource is not provided, we will validate the actual resource
        // record, otherwise, we will validate the via_resource record e.q. user can see contact
        // and it's calls and a comment is added to the call
        if (! $request->viaResource()) {
            $this->authorize('view', $request->record());
        } else {
            $this->authorize(
                'view',
                $request->findResource($request->via_resource)->newModel()->find($request->via_resource_id)
            );
        }

        $comment = $request->record()->addComment($input);

        return $this->response(
            new CommentResource($comment),
            JsonResponse::HTTP_CREATED
        );
    }

    /**
     * Display the given comment.
     */
    public function show(string $id): JsonResponse
    {
        $comment = Comment::with('creator')->findOrFail($id);

        $this->authorize('view', $comment);

        return $this->response(new CommentResource($comment));
    }

    /**
     * Update the given comment.
     */
    public function update(string $id, Request $request): JsonResponse
    {
        $comment = Comment::findOrFail($id);

        $this->authorize('update', $comment);

        $input = $request->validate([
            'body' => 'required|string',
            'via_resource' => [
                'sometimes',
                'required_with:via_resource_id',
                'string',
                Rule::in(Innoclapps::registeredResources()
                    ->whereInstanceOf(PipesComments::class)
                    ->map(fn (Resource $resource) => $resource->name())->all()),
            ],
            'via_resource_id' => [
                'sometimes',
                'numeric',
                'required_with:via_resource',
                Rule::requiredIf(in_array($comment->commentable->resource()->name(), ['notes', 'calls'])),
            ],
        ]);

        $mention = new PendingMention($input['body']);
        $input['body'] = $mention->getUpdatedText();

        $comment->fill($input)->save();

        $comment->notifyMentionedUsers(
            $mention,
            $input['via_resource'] ?? null,
            $input['via_resource_id'] ?? null
        );

        $comment->loadMissing('creator');

        return $this->response(new CommentResource(
            $comment
        ));
    }

    /**
     * Remove the given comment from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $comment = Comment::findOrFail($id);

        $this->authorize('delete', $comment);

        $comment->delete();

        return $this->response('', JsonResponse::HTTP_NO_CONTENT);
    }
}
