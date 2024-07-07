<?php
 

namespace Modules\Core\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Modules\Core\App\Database\Migrator;

class MigrateController extends Controller
{
    /**
     * Show the migration required action.
     */
    public function show(Migrator $migrator): View
    {
        abort_unless($migrator->needed(), 404);

        return view('core::migrate');
    }

    /**
     * Perform migration.
     */
    public function migrate(Migrator $migrator): void
    {
        abort_unless($migrator->needed(), 404);

        $migrator->run();
    }
}
