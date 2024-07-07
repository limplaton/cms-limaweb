<?php
 

namespace Modules\Core\App\Http\Controllers\Api\Resource;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Modules\Core\App\Contracts\Resources\Importable;
use Modules\Core\App\Facades\ChangeLogger;
use Modules\Core\App\Http\Controllers\ApiController;
use Modules\Core\App\Http\Requests\ResourceRequest;
use Modules\Core\App\Http\Resources\ImportResource;
use Modules\Core\App\Models\Import;
use Modules\Core\App\Resource\Import\RowsExceededException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImportController extends ApiController
{
    /**
     * Get the import files in storage for the resource.
     */
    public function index(ResourceRequest $request): AnonymousResourceCollection
    {
        abort_unless($request->resource() instanceof Importable, 404);

        $result = Import::query()
            ->with('user')
            ->unless($request->user()->isSuperAdmin(), function (Builder $query) use ($request) {
                $query->where('user_id', $request->user()->getKey());
            })
            ->byResource($request->resource()->name())
            ->latest()
            ->get();

        return ImportResource::collection($result);
    }

    /**
     * Perform import for the current resource.
     */
    public function handle(ResourceRequest $request): JsonResponse
    {
        abort_unless($request->resource() instanceof Importable, 404);

        $import = Import::findOrFail($request->route('id'));

        $this->authorize('import', $import);

        if (! $import->nextBatch()) {
            $request->validate([
                'mappings' => 'required|array',
                'mappings.*.attribute' => 'nullable|distinct|string',
                'mappings.*.auto_detected' => 'required|boolean',
                'mappings.*.original' => 'required|string',
                'mappings.*.skip' => 'required|boolean',
                'mappings.*.detected_attribute' => 'present',
            ]);

            // Update with the user provided mappings
            $import->data['mappings'] = $request->mappings;

            $import->save();
        }

        $this->increasePhpIniValues();

        try {
            $request->resource()->importable()->perform($import);

            return $this->response(new ImportResource($import->loadMissing('user')));
        } catch (Exception|RowsExceededException $e) {
            if ($e instanceof RowsExceededException) {
                $deleted = $import->delete();
            }

            return $this->response([
                'message' => $e->getMessage(),
                'deleted' => $deleted ?? false,
                'rows_exceeded' => $e instanceof RowsExceededException,
            ], 500);
        }
    }

    /**
     * Initiate new import and start mapping.
     */
    public function upload(ResourceRequest $request): JsonResponse
    {
        abort_unless($request->resource() instanceof Importable, 404);

        $request->validate(['file' => 'required|mimes:csv,txt']);

        $import = $request->resource()
            ->importable()
            ->upload(
                $request->file('file'),
                $request->user()
            );

        return $this->response(new ImportResource($import->loadMissing('user')));
    }

    /**
     * Download sample import file.
     */
    public function sample(ResourceRequest $request): BinaryFileResponse
    {
        abort_unless($request->resource() instanceof Importable, 404);

        $totalRows = $request->get('total_rows', 1);

        return $request->resource()->importSample($totalRows)->download();
    }

    /**
     * Revert the import.
     *
     * The request must be made in batches until there are no records available.
     * 99% of customers does not use queue, hence, we cannot queue a job.
     */
    public function revert(ResourceRequest $request)
    {
        $import = Import::findOrFail($request->route('id'));

        abort_unless($import->isRevertable(), 404);

        $this->authorize('revert', $import);

        $reverted = 0;

        ChangeLogger::disable();

        DB::transaction(function () use ($request, $import, &$reverted) {
            foreach ($request->resource()
                ->newQuery()
                ->where('import_id', $import->getKey())
                ->limit($request->input('limit', 500))
                ->get() as $model) {
                if ($model?->forceDelete() ?? $model->delete()) {
                    if ($model->logsModelChanges()) {
                        $model->changelog()->delete();
                    }

                    if ($import->imported > 0) {
                        $import->imported--;
                    }

                    $reverted++;
                }
            }
        });

        $import->save();

        return $this->response(new ImportResource($import->loadMissing('user')));
    }

    /**
     * Delete the given import.
     */
    public function destroy(ResourceRequest $request): JsonResponse
    {
        abort_unless($request->resource() instanceof Importable, 404);

        $import = Import::findOrFail($request->route('id'));

        $this->authorize('delete', $import);

        $import->delete();

        return $this->response('', JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * @codeCoverageIgnore
     */
    protected function increasePhpIniValues(): void
    {
        if (! app()->runningUnitTests()) {
            \DetachedHelper::raiseMemoryLimit('256M');
            @ini_set('max_execution_time', 300);
        }
    }
}
