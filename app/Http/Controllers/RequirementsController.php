<?php
 

namespace App\Http\Controllers;

use App\Installer\PermissionsChecker;
use App\Installer\RequirementsChecker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\View\View;
use Modules\Core\App\Environment;

class RequirementsController extends Controller
{
    /**
     * Shows the requirements page.
     */
    public function show(RequirementsChecker $requirements, PermissionsChecker $permissions): View
    {
        $php = $requirements->checkPHPversion();
        $requirements = $requirements->check();
        $permissions = $permissions->check();

        ViewFacade::share(['withSteps' => false]);

        return view('requirements', [
            'php' => $php,
            'requirements' => $requirements,
            'permissions' => $permissions,
        ]);
    }

    /**
     * Confirm the requirements
     */
    public function confirm(): RedirectResponse
    {
        Environment::capture();

        return redirect()->back();
    }
}
