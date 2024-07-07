<?php
 

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Core\App\Facades\Innoclapps;
use Symfony\Component\HttpFoundation\Response;

class PreventInstallationWhenInstalled
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (config('app.env') === 'production') {
            if ($request->route()->getName() === 'install.finished') {
                /**
                 * Uses signed URL Laravel feature as when the installation
                 * is finished the installed file will be created and if this action
                 * is in the PreventInstallationWhenInstalled middleware, it will show 404 error as the installed
                 * file will exists but we need to show the user that the installation is finished
                 */
                if (! $request->hasValidSignature()) {
                    abort(401);
                }
            } elseif (Innoclapps::isAppInstalled()) {
                abort(404);
            }
        }

        return $next($request);
    }
}
