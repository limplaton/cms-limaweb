<?php
 

namespace Modules\Core\App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Core\App\Updater\UpdateFinalizer;
use Symfony\Component\HttpFoundation\Response;

class PreventRequestsWhenUpdateNotFinished
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        if (app(UpdateFinalizer::class)->needed()) {
            return redirect('/update/finalize');
        }

        return $next($request);
    }
}
