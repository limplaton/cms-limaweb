<?php
 

namespace Modules\Core\App\Http\Controllers;

use DateTime;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Modules\Core\App\Facades\Innoclapps;

class StyleController extends Controller
{
    /**
     * Serve the requested stylesheet.
     */
    public function show(Request $request): Response
    {
        $path = Arr::get(Innoclapps::styles(), $request->style);

        abort_if(is_null($path), 404);

        return response(
            file_get_contents($path),
            200,
            [
                'Content-Type' => 'text/css',
            ]
        )->setLastModified(DateTime::createFromFormat('U', (string) filemtime($path)));
    }
}
