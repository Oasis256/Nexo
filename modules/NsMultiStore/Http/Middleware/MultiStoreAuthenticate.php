<?php

namespace Modules\NsMultiStore\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class MultiStoreAuthenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, ...$guards)
    {
        if (! Auth::check()) {
            return redirect(ns()->route('ns.login'));
        }

        return $next($request);
    }
}
