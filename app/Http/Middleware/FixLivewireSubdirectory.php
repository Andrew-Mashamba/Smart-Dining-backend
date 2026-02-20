<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fix Livewire's data-update-uri for sub-directory deployments.
 *
 * When APP_URL includes a path (e.g. /Smart-Dining), Livewire's
 * getUpdateUri() returns "/livewire/update" without the prefix.
 * The browser then POSTs to the wrong URL and gets a 404.
 * This middleware rewrites the attribute in the rendered HTML.
 */
class FixLivewireSubdirectory
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $appPath = parse_url(config('app.url', ''), PHP_URL_PATH);

        if (! $appPath || $appPath === '/' || ! method_exists($response, 'getContent')) {
            return $response;
        }

        $content = $response->getContent();

        if ($content && str_contains($content, 'data-update-uri=')) {
            $prefix = rtrim($appPath, '/');

            // Fix data-update-uri="/livewire/update" → data-update-uri="/Smart-Dining/livewire/update"
            $content = str_replace(
                'data-update-uri="/livewire/update"',
                'data-update-uri="'.$prefix.'/livewire/update"',
                $content
            );

            // Fix the JSON config variant used by @livewireScriptConfig
            $content = str_replace(
                '"uri":"\/livewire\/update"',
                '"uri":"'.str_replace('/', '\\/', $prefix).'\\/livewire\\/update"',
                $content
            );

            $response->setContent($content);
        }

        return $response;
    }
}
