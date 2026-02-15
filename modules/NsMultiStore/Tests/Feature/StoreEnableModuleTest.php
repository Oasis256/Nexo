<?php
namespace Modules\NsMultiStore\Tests\Feature;

use App\Services\ModulesService;
use Tests\TestCase;

class StoreEnableModuleTest extends TestCase
{
    public function testEnableMultiStoreModule()
    {
        /**
         * @var ModulesService $modulesService
         */
        $modulesService     =   app()->make( ModulesService::class );
        $modulesService->enable( 'NsMultiStore' );

        /**
         * if NsGastro module is available
         * we'll enable it for performing tests
         */
        $module = $modulesService->get( 'NsGastro' );
        if ( ! empty( $module ) ) {
            $modulesService->enable( 'NsGastro' );
            $modulesService->runAllMigration( 'NsGastro' );
        }

        /**
         * We'll check if the module is correctly enabled
         * and set on the enabled_module option.
         */
        $enableModules  =   ns()->option->get( 'enabled_modules' );

        $this->assertContains( 'NsMultiStore', $enableModules );

        /**
         * We'll make sure all the module
         * migration are executed
         */
        $modulesService->runAllMigration( 'NsMultiStore' );
    }
}