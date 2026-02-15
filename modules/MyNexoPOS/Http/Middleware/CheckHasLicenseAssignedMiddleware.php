<?php

namespace Modules\MyNexoPOS\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckHasLicenseAssignedMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (! ns()->option->get('mynexopos_license', false)) {
            return redirect(route('mynexopos.select-license'));
        }

        return $next($request);
    }
}
