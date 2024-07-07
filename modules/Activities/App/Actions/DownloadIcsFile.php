<?php
 

namespace Modules\Activities\App\Actions;

use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Modules\Core\App\Actions\Action;
use Modules\Core\App\Actions\ActionFields;
use Modules\Core\App\Http\Requests\ActionRequest;

class DownloadIcsFile extends Action
{
    /**
     * Indicates that the action does not have confirmation dialog.
     */
    public bool $withoutConfirmation = true;

    /**
     * The XHR response type that should be passed from the front-end.
     */
    public string $responseType = 'blob';

    /**
     * Handle method.
     */
    public function handle(Collection $models, ActionFields $fields): Response
    {
        if ($models->count() > 1) {
            return static::error('Please run this on only one activity.');
        }

        $activity = $models->first();

        return response($activity->generateICSInstance()->get(), 200, [
            'Content-Type' => 'text/calendar',
            'Content-Disposition' => 'attachment; filename='.$activity->icsFilename().'.ics',
            'charset' => 'utf-8',
        ]);
    }

    /**
     * @param  \Illumindate\Database\Eloquent\Model  $model
     */
    public function authorizedToRun(ActionRequest $request, $model): bool
    {
        return $request->user()->can('view', $model);
    }

    /**
     * Action name.
     */
    public function name(): string
    {
        return __('activities::activity.download_ics');
    }
}
