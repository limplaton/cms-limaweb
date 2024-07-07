<?php
 

namespace Modules\Core\App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Core\App\Database\Migrator;
use Symfony\Component\HttpFoundation\Response;

class PreventRequestsWhenMigrationNeeded
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        if (app(Migrator::class)->needed()) {
            return redirect('/migrate');
        }

        return $next($request);
    }
}
