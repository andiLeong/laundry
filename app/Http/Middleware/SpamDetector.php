<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SpamDetector
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next, $name = null): Response
    {
        $config = config('honeypot');
        $name ??= $config['name'];

        if (!$config['enabled']) {
            return $next($request);
        }

        if (!empty($request->get($name))) {
            abort($config['code'], $config['message']);
        }

        return $next($request);
    }
}
