<?php

namespace Modules\NsMultiStore\Http\Middleware;

use App\Exceptions\NotAllowedException;
use App\Exceptions\NotFoundException;
use App\Services\Options;
use Closure;
use Illuminate\Http\Request;
use Modules\NsMultiStore\Events\MultiStoreApiRoutesExecutedEvent;
use Modules\NsMultiStore\Events\MultiStoreWebRoutesExecutedEvent;
use Modules\NsMultiStore\Models\Store;
use Modules\NsMultiStore\Services\StoresService;

class DetectStoreMiddleware
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
        if (! empty($request->route('substore'))) {
            $store = Store::where('slug', $request->route('substore'))->first();
        } else {
            $store = Store::find($request->route('store_id'));
        }

        if ($request->getPathInfo() === '/dashboard' && ! $store instanceof Store) {
            return redirect(route('ns.multistore-dashboard'));
        }

        if (! $store instanceof Store) {
            throw new NotFoundException(__m('Unable to find the requested store.', 'NsMultiStore'));
        }

        if ($store->status !== Store::STATUS_OPENED) {
            throw new NotAllowedException(__m('Unable to access to a store that is not opened.', 'NsMultiStore'));
        }

        $request->route()->forgetParameter('store_id');
        $request->route()->forgetParameter('substore');

        /**
         * @var StoresService
         */
        $storeService = app()->make(StoresService::class);
        $storeService->setStore($store);

        if ($type === 'web') {
            event(new MultiStoreWebRoutesExecutedEvent($store));
        } else {
            event(new MultiStoreApiRoutesExecutedEvent($store));
        }

        return $next($request);
    }
}
