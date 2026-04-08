<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Prevent the visual-editor iframe from interfering with the admin session.
 *
 * The editor loads the storefront in an iframe (/?_designMode=...).  That iframe
 * (and every sub-request it triggers — images, Livewire, etc.) shares the same
 * origin as the admin panel, so the browser sends the admin session cookie.
 *
 * Without this middleware the iframe's StartSession would load and mutate the
 * admin session, effectively logging the admin out for subsequent API calls.
 *
 * Fix: switch the session cookie name to a separate "preview" cookie before
 * StartSession runs, so the iframe gets its own independent session and the
 * admin session is never touched.
 */
class PreserveSessionInDesignMode
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isDesignModeRequest($request)) {
            config(['session.cookie' => config('session.cookie', 'bagisto_session').'_preview']);
        }

        return $next($request);
    }

    /**
     * Determine if this request originates from the visual-editor iframe.
     *
     * Matches the initial iframe URL (?_designMode=...) as well as any
     * sub-request whose Referer contains the design-mode flag (images,
     * Livewire updates, etc.).
     */
    protected function isDesignModeRequest(Request $request): bool
    {
        if ($request->has('_designMode') || $request->has('_previewMode')) {
            return true;
        }

        $referer = $request->headers->get('referer', '');

        return str_contains($referer, '_designMode') || str_contains($referer, '_previewMode');
    }
}
