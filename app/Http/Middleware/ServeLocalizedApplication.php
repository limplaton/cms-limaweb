<?php
 

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Core\App\Facades\Innoclapps;
use Symfony\Component\HttpFoundation\Response;

class ServeLocalizedApplication
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($locale = $this->determineLocale($request)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }

    /**
     * Determine the locale for the current request
     */
    protected function determineLocale(Request $request): ?string
    {
        $locale = $this->determineUserLocale($request);
        $locales = Innoclapps::locales();

        if (is_null($locale)) {
            // User not logged in, try to determine the locale from the request
            $locale = $request->getPreferredLanguage($locales);
        }

        if (in_array($locale, $locales)) {
            return $locale;
        }

        return null;
    }

    /**
     * Determine the user locale,
     */
    protected function determineUserLocale(Request $request): ?string
    {
        // Check if there is a user in the request, if so,
        // we will retireve the locale from the user preferred locale
        if ($request->user()) {
            return $request->user()->preferredLocale();
        } elseif (! $request->is(\Modules\Core\App\Application::API_PREFIX.'/*') && $request->session()->has('locale')) {
            // Usually used when initializing the application or after the user is logged out
            return $request->session()->get('locale');
        }

        return null;
    }
}
