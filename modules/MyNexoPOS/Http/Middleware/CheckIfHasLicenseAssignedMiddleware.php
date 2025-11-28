<?php

namespace Modules\MyNexoPOS\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckIfHasLicenseAssignedMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (ns()->option->get('mynexopos_license')) {
            return redirect(route('mynexopos.update'));
        }

        return $next($request);
    }
}
