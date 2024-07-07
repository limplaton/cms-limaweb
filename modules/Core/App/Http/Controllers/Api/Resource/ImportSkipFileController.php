<?php
 

namespace Modules\Core\App\Http\Controllers\Api\Resource;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Modules\Core\App\Contracts\Resources\Importable;
use Modules\Core\App\Http\Controllers\ApiController;
use Modules\Core\App\Http\Requests\ResourceRequest;
use Modules\Core\App\Http\Resources\ImportResource;
use Modules\Core\App\Models\Import;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportSkipFileController extends ApiController
{
    /**
     * Upload the fixed skip file and start mapping.
     */
    public function upload(ResourceRequest $request): JsonResponse
    {
        abort_unless($request->resource() instanceof Importable, 404);

        $import = Import::findOrFail($request->route('id'));

        abort_if(is_null($import->skip_file_path), 404);

        $this->authorize('uploadFixedSkipFile', $import);

        $request->validate(['skip_file' => 'required|mimes:csv,txt']);

        $request->resource()
            ->importable()
            ->uploadViaSkipFile(
                $request->file('skip_file'),
                $import
            );

        $import->loadMissing('user');

        return $this->response(new ImportResource($import));
    }

    /**
     * Download the skip file for the import.
     */
    public function download(ResourceRequest $request): StreamedResponse
    {
        abort_unless($request->resource() instanceof Importable, 404);

        $import = Import::findOrFail($request->route('id'));

        abort_if(is_null($import->skip_file_path), 404);

        $this->authorize('downloadSkipFile', $import);

        return Storage::disk($import::disk())->download(
            $import->skip_file_path,
            $filename = $import->skip_file_filename,
            [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename='.$filename,
                'charset' => 'utf-8',
            ]
        );
    }
}
