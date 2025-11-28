<?php

namespace Modules\MyNexoPOS\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckHasTokenMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (! ns()->option->get('mynexopos_secret_key', false) || ! ns()->option->get('mynexopos_app_id', false)) {
            return redirect(route('mynexopos.authentify'));
        }

        return $next($request);
    }
}
