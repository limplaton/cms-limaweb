<?php
 

namespace Modules\Core\App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\ResponseTrait;
use Modules\Core\App\Application;
use Symfony\Component\HttpFoundation\Response;

class AddVersionHeaderToResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (in_array(ResponseTrait::class, class_uses_recursive($response::class))) {
            $response->withHeaders(['X-App-Version' => Application::VERSION]);
        }

        return $response;
    }
}
