<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreserveSessionInDesignMode
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        file_put_contents('/tmp/middleware_debug.log', date('Y-m-d H:i:s') . ' MIDDLEWARE HIT: ' . $request->fullUrl() . ' has_designMode=' . ($request->has('_designMode') ? 'true' : 'false') . PHP_EOL, FILE_APPEND);

        if ($request->has('_designMode') || $request->has('_previewMode')) {
            $sessionName = config('session.cookie', 'bagisto_session');

            $cookies = $response->headers->getCookies();

            $response->headers->remove('set-cookie');

            foreach ($cookies as $cookie) {
                if ($cookie->getName() !== $sessionName) {
                    $response->headers->setCookie($cookie);
                }
            }

            file_put_contents('/tmp/middleware_debug.log', date('Y-m-d H:i:s') . ' STRIPPED session cookie. Original count: ' . count($cookies) . PHP_EOL, FILE_APPEND);
        }

        return $response;
    }
}
