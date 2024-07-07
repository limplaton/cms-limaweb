<?php
 

namespace Modules\Core\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Modules\Core\App\Updater\UpdateFinalizer;

class FinalizeUpdateController extends Controller
{
    /**
     * Show the update finalization action.
     */
    public function show(UpdateFinalizer $finalizer): View
    {
        abort_unless($finalizer->needed(), 404);

        return view('core::update.finalize');
    }

    /**
     * Perform update finalization.
     */
    public function finalize(UpdateFinalizer $finalizer): void
    {
        abort_unless($finalizer->needed(), 404);

        $finalizer->run();
    }
}
