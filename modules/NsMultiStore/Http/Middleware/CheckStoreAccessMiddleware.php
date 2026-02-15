<?php

namespace Modules\NsMultiStore\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\NsMultiStore\Services\StoresService;

class CheckStoreAccessMiddleware
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
         * @var StoresService
         */
        $storeService = app()->make(StoresService::class);
        $storeService->checkStoreAccessibility();

        return $next($request);
    }
}
