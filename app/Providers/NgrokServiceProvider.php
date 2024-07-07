<?php
 

namespace App\Providers;

use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\ServiceProvider;

/**
 * @codeCoverageIgnore
 */
class NgrokServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            $urlGenerator = $this->app->make('url');
            $request = $this->app->make('request');

            $this->forceNgrokSchemeHost($urlGenerator, $request);
        }
    }

    /**
     * Force the url generator to the ngrok scheme://host.
     */
    protected function forceNgrokSchemeHost(UrlGenerator $urlGenerator, Request $request): void
    {
        $host = $this->extractOriginalHost($request);

        if ($this->isNgrokHost($host)) {
            $scheme = $this->extractOriginalScheme($request);
            $urlGenerator->forceScheme($scheme);
            $urlGenerator->forceRootUrl($url = $scheme.'://'.$host);

            $this->app->config->set([
                'app.url' => $url,
                'sanctum.stateful' => [$host],
                'session.domain' => '.'.$host,
                'app.ngrok' => true,
            ]);

            Paginator::currentPathResolver(fn () => $urlGenerator->to($request->path()));
        }
    }

    /**
     * Extract the original scheme from the request.
     */
    protected function extractOriginalScheme(Request $request): string
    {
        if ($request->hasHeader('x-forwarded-proto')) {
            $scheme = $request->header('x-forwarded-proto');
        } else {
            $scheme = $request->getScheme();
        }

        return $scheme;
    }

    /**
     * Extract the original host from the request.
     */
    protected function extractOriginalHost(Request $request): string
    {
        if ($request->hasHeader('x-original-host')) {
            $host = $request->header('x-original-host');
        } elseif ($request->hasHeader('x-forwarded-host')) { // windows ngrok with the option --host-header
            $host = $request->header('x-forwarded-host');
        } else {
            $host = $request->getHost();
        }

        return $host;
    }

    /**
     * Check if the host from ngrok.
     */
    protected function isNgrokHost(string $host): bool
    {
        return preg_match('/^[a-z0-9-]+\.ngrok-free\.app$/i', $host) || preg_match('/^[a-z0-9-]+\.ngrok\.io$/i', $host);
    }
}
