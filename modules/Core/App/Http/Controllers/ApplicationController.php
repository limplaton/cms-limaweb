<?php
 

namespace Modules\Core\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class ApplicationController extends Controller
{
    /**
     * Application main view.
     */
    public function __invoke(): View
    {
        return view('core::app');
    }
}
