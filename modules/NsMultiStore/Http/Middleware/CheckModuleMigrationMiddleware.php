<?php

namespace Modules\NsMultiStore\Http\Middleware;

use App\Services\ModulesService;
use Modules\NsMultiStore\Models\Store;
use Modules\NsMultiStore\Services\StoresService;

class CheckModuleMigrationMiddleware
{
    public function handle($request, $next)
    {
        /**
         * @var ModulesService
         */
        $module = app()->make(ModulesService::class);

        /**
         * @var Store
         */
        $store = Store::current();

        /**
         * @var StoresService
         */
        $storeService = app()->make(StoresService::class);
        $migrations = $storeService->getModuleMigrations();
        $systemMigrations = $storeService->getSystemMigrations();
        $executed = $storeService->getExecutedMigration($store)
            ->map(fn ($migration) => $migration->file);

        foreach ($migrations as $file) {
            if (! in_array($file, $executed->toArray())) {
                $request->session()->flash('multistore-cb', $request->url());

                return redirect()->route('ns.multistore-migrate', [
                    'store'     =>     $store->id,
                ]);
            }
        }

        foreach ($systemMigrations as $file) {
            if (! in_array($file, $executed->toArray())) {
                $request->session()->flash('multistore-cb', $request->url());

                return redirect()->route('ns.multistore-migrate', [
                    'store'     =>     $store->id,
                ]);
            }
        }

        return $next($request);
    }
}
