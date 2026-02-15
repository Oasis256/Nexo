<?php

namespace Modules\NsMultiStore\Listeners;

use App\Events\CrudAfterDeleteEvent;
use Modules\NsMultiStore\Crud\StoreCrud;
use Modules\NsMultiStore\Services\StoresService;

class CrudAfterDeleteEventListener
{
    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return  void
     */
    public function handle( CrudAfterDeleteEvent $event )
    {
        if ($event->resource instanceof StoreCrud) {
            $role_ids = (array) json_decode($event->model->roles_id);

            /**
             * @var StoresService
             */
            $storesService = app()->make(StoresService::class);
            $storesService->countRoleStoresUsingID($role_ids);
        }
    }
}
