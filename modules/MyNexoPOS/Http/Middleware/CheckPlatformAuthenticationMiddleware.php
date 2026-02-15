<?php

namespace Modules\MyNexoPOS\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPlatformAuthenticationMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (! ns()->option->get('mynexopos_refresh_token', false)) {
            return redirect(route('mynexopos.authentify'));
        }

        return $next($request);
    }
}
