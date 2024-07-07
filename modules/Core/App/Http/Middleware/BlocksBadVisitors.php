<?php
 

namespace Modules\Core\App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\Core\App\Facades\Innoclapps;
use Symfony\Component\HttpFoundation\Response;

class BlocksBadVisitors
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldSkip()) {
            return $next($request);
        }

        if (in_array($request->userAgent(), $this->getBadUserAgents())) {
            abort(403);
        }

        $referer = $request->headers->get('referer') ?: null;

        if ($referer && in_array($referer, $this->getBadReferrers())) {
            abort(403);
        }

        if (in_array($request->ips(), $this->getBadIps())) {
            abort(403);
        }

        return $next($request);
    }

    /**
     * Check whether the checks should be skipped.
     */
    protected function shouldSkip(): bool
    {
        return Auth::check() ||
            settings('block_bad_visitors') === false ||
            app()->runningUnitTests() ||
            ! Innoclapps::isAppInstalled();
    }

    /**
     * Get bad referrers.
     */
    protected function getBadReferrers(): array
    {
        return $this->getList('bad-referrers');
    }

    /**
     * Get bad ips.
     */
    protected function getBadIps(): array
    {
        return $this->getList('bad-ip-addresses');
    }

    /**
     * Get bad user agents.
     */
    protected function getBadUserAgents(): array
    {
        return $this->getList('bad-user-agents');
    }

    /**
     * Get list.
     */
    protected function getList(string $type): array
    {
        return Cache::remember('bv-'.$type, now()->addDay(1), function () use ($type) {
            $response = Http::withoutVerifying()->get($this->getListUrl($type));

            if ($response->successful()) {
                return explode("\n", trim($response->body()));
            }

            return [];
        });
    }

    /**
     * Get the type list request URL.
     */
    protected function getListUrl(string $type): string
    {
        $repository = 'mitchellkrogza/nginx-ultimate-bad-bot-blocker';

        return 'https://raw.githubusercontent.com/'.$repository.'/master/_generator_lists/'.$type.'.list';
    }
}
