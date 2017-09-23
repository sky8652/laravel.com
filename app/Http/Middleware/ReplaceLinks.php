<?php

namespace App\Http\Middleware;

use Closure;

class ReplaceLinks
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        return $this->replaceLinks($request, $next($request));
    }

    /**
     * Replace links for response content.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\Response  $response
     * @return \Illuminate\Http\Response
     */
    protected function replaceLinks($request, $response)
    {
        // Replace path in a.href to `url(path)` for path links
        $content = preg_replace_callback(
            '#(<a[^>]+href=["\'])([^"\']+)#i',
            function ($matches) {
                return $matches[1].url($matches[2]);
            },
            $response->getContent()
        );

        // Replace https?://laravel.com/docs to `url('docs')` for docs routes
        $content = preg_replace_callback(
            '#https?://laravel.com/(docs)#i',
            function ($matches) {
                return url($matches[1]);
            },
            $content
        );

        // Replace https?://laravel.com/ to `$rootURL/` for public assets and api links
        $content = preg_replace(
            '#https?://laravel.com/#i',
            request()->root().'/',
            $content
        );

        return $response->setContent($content);
    }
}
