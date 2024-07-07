<?php
 

namespace Modules\Core\App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Modules\Core\App\Http\Controllers\ApiController;
use Modules\Core\App\Http\Requests\WorkflowRequest;
use Modules\Core\App\Http\Resources\WorkflowResource;
use Modules\Core\App\Models\Workflow;

class WorkflowController extends ApiController
{
    /**
     * Get the stored workflows.
     */
    public function index(): JsonResponse
    {
        return $this->response(
            WorkflowResource::collection(Workflow::get())
        );
    }

    /**
     * Display the specified workflow..
     */
    public function show(Workflow $workflow): JsonResponse
    {
        return $this->response(new WorkflowResource($workflow));
    }

    /**
     * Store a newly created workflow in storage.
     */
    public function store(WorkflowRequest $request): JsonResponse
    {
        $workflow = new Workflow($request->createData());

        $workflow->save();

        return $this->response(
            new WorkflowResource($workflow),
            JsonResponse::HTTP_CREATED
        );
    }

    /**
     * Update the specified workflow in storage.
     */
    public function update(Workflow $workflow, WorkflowRequest $request): JsonResponse
    {
        $workflow->fill($request->createData());

        $workflow->save();

        return $this->response(new WorkflowResource($workflow));
    }

    /**
     * Remove the specified workflow from storage.
     */
    public function destroy(Workflow $workflow): JsonResponse
    {
        $workflow->delete();

        return $this->response('', JsonResponse::HTTP_NO_CONTENT);
    }
}
