<?php

namespace Modules\NsMultiStore\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProtectMultistoreRootMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $type = 'web')
    {
        /**
         * Only users able to access the multistore
         * will be able to login.
         */
        if ( ns()->allowedTo('ns.multistore.access.root')) {
            return $next($request);
        }

        return redirect(route('ns.multistore-select'));
    }
}
