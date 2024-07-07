<?php
 

namespace Modules\Users\App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Users\App\Models\User;
use Symfony\Component\HttpFoundation\Response;

class UpdateUserLastActiveDate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->shouldPerformUpdate($request)) {
            $this->updateLastActiveDate();
        }

        return $response;
    }

    /**
     * Check whether the last active date update should be performed.
     */
    protected function shouldPerformUpdate(Request $request): bool
    {
        // We will only update the last_active_at each minute, as it does not
        // makes sense to update the last_active_at every request.

        return ! $request->isZapier() &&
            Auth::check() &&
            (! Auth::user()->last_active_at || Auth::user()->last_active_at->diffInMinutes(now()) >= 1);
    }

    /**
     * Update the current user last active date.
     */
    protected function updateLastActiveDate(): void
    {
        User::withoutTimestamps(function () {
            User::where('id', Auth::id())->update([
                'last_active_at' => now(),
            ]);
        });
    }
}
