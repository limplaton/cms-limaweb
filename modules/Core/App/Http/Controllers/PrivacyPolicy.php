<?php
 

namespace Modules\Core\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class PrivacyPolicy extends Controller
{
    /**
     * Display the privacy policy.
     */
    public function __invoke(): View
    {
        $content = clean(settings('privacy_policy'));

        return view('core::privacy-policy', compact('content'));
    }
}
