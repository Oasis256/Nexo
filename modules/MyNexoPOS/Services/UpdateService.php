<?php

namespace Modules\MyNexoPOS\Services;

use App\Services\ModulesService;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class UpdateService
{
    private $domain = 'https://my.nexopos.com';

    public function updateTo($release)
    {
        $this->pruneTelescopeRecords();
        $this->backupDatabase();
        $this->clearAllPossibleCache();
        $this->cleaningUpdateDirectories();
        $this->downloadRelease($release);
        $this->extractUpdate();
        $this->restoreModulesSymlink();
        $this->cleaningUpdateDirectories();
        $this->updateComposerDependencies();

        return [
            'status'    =>  'success',
            'message'   =>  sprintf(
                __('You have successfully been updated to %s'),
                $release['version'],
            ),
        ];
    }

    /**
     * Will restore all modules symlink deleted
     * along with the public folder
     *
     * @param void
     * @return void
     */
    public function restoreModulesSymlink()
    {
        /**
         * @var ModulesService
         */
        $modules = app()->make(ModulesService::class);

        foreach ($modules->get() as $namespace => $module) {
            $this->artisan('modules:symlink '.$namespace);
            $this->artisan('ns:translate '.$namespace.' --extract --lang=all --symlink');
        }

        return [
            'status'    =>  'success',
            'message'   =>  __('The module symlink has been restored/refreshed'),
        ];
    }

    /**
     * Will attempt toupdate
     * composer dependencies
     *
     * @return array $response
     */
    public function updateComposerDependencies()
    {
        try {
            $process = new Process([
                ( new ExecutableFinder )->find( env( 'MYNEXOPOS_COMPOSER', 'composer' ), 'composer'),
                'update',
            ], base_path());

            $process->mustRun();

            return [
                'status'    =>  'success',
                'messsage'  =>  __('Composer dependencies has been updated.'),
                'data'      =>  [
                    'output'    =>  $process->getOutput(),
                ],
            ];
        } catch (ProcessFailedException $exception) {
            throw new Exception(
                sprintf(
                    __('Unable to update composer. Maybe composer is not installed. Additional Hint : %s '),
                    $exception->getMessage()
                )
            );
        }
    }

    public function cleaningUpdateDirectories()
    {
        exec('rm '.base_path('mns-download').' -rf');
        exec( 'mkdir '.base_path( 'mns-download' ) );
    }

    /**
     * Will prune all telescope records
     *
     * @param void
     * @return void
     */
    public function pruneTelescopeRecords()
    {
        $this->artisan( 'telescope:prune --hours=0' );
        $this->artisan( 'cache:clear' );
    }

    /**
     * Will backup the database before updating
     *
     * @return void
     */
    public function backupDatabase()
    {
        $date = ns()->date->toDateTimeString();
        $slug = Str::slug($date);

        $this->artisan( 'snapshot:create mns-backup-' . $slug );
    }

    private function artisan($command)
    {
        $process = new Process([
            ( new ExecutableFinder )->find( env( 'MYNEXOPOS_PHP', 'php' ), 'php'),
            'artisan',
            ...explode(' ', $command),
        ], base_path());

        $process->mustRun();

        return [
            'status'    =>  'success',
            'messsage'  =>  __('The command has been executed.'),
            'data'      =>  [
                'output'    =>  $process->getOutput(),
            ],
        ];
    }

    /**
     * Will clear all possible cache
     * to reduce storage size
     */
    public function clearAllPossibleCache()
    {
        $this->artisan('clear');
        $this->artisan('view:clear');
        $this->artisan('cache:clear');

        return [
            'status'    =>  'success',
            'message'   =>  __('All the cache has been cleared'),
        ];
    }

    public function downloadRelease($release)
    {
        if (! is_dir(base_path('mns-download'))) {
            mkdir(base_path('mns-download'));
        }

        $domain = parse_url(url('/'));
        $response = Http::withHeaders([
            'Accept'        => 'application/json',
            'Authorization' => 'Bearer '.ns()->option->get('mynexopos_access_token'),
        ])->post($this->domain.'/api/user/core-release/download', [
            'reference'     =>  31188619,
            'domain'        =>  $domain['host'].($domain['path'] ?? ''),
            'release'       =>  $release,
            'license'       =>  ns()->option->get('mynexopos_license'),
        ]);

        if ($response->failed()) {
            throw new Exception( $response->json()[ 'message' ] );
        }

        Storage::disk('ns')->put('mns-download'.DIRECTORY_SEPARATOR.'update.zip', $response->body());

        if (! Storage::disk('ns')->exists('mns-download'.DIRECTORY_SEPARATOR.'update.zip')) {
            throw new Exception(__('Unable to download the update. Maybe a folder permissions issue.'));
        }
    }

    /**
     * Will extract the update
     *
     * @return array $response
     */
    public function extractUpdate()
    {
        exec('cd '.base_path('mns-download').' && unzip '.'update.zip');

        $downloadDirectories = Storage::disk('ns')->directories('mns-download');
        $totalDirectories = count($downloadDirectories);

        if ($totalDirectories === 1) {
            /**
             * CAUTION: will delete files on the projects
             */
            foreach (Storage::disk('ns')->directories() as $directory) {
                if ( ! in_array( $directory, [
                    'storage', 'modules', '.git'
                ])) {
                    Storage::disk( 'ns' )->deleteDirectory( $directory );
                }
            }

            exec('cp '.base_path($downloadDirectories[0]).'/* '.base_path().' -r');
            exec('rm '.base_path($downloadDirectories[0]).' -rf');
        } else {
            throw new Exception(__('Too many directories on the download directory'));
        }
    }

    /**
     * Checks if there are some modules
     * that requires composer to be installed
     *
     * @return void
     */
    public function checkModulesVendor()
    {
        /**
         * @var Collection
         */
        $missingComposer = $this->getModulesRequiringComposer();

        /**
         * If some modules needs
         * to have composer installed
         * this will redirect to the installation page
         */
        if ($missingComposer->isNotEmpty() && env('MNS_INSTALL_PACKAGES', false)) {
            Redirect::to(route('mynexopos.modules-vendors'))->send();
        }
    }

    /**
     * Will return all modules that needs
     * composer to be installed
     *
     * @return Collection
     */
    public function getModulesRequiringComposer(): Collection
    {
        $modulesService = app()->make(ModulesService::class);

        $allModules = $modulesService->get();

        return collect($allModules)->filter(function ($module, $moduleNamespace) {
            if ($moduleNamespace !== 'MyNexoPOS') {
                return $module['requires-composer'] && ! $module['composer-installed'];
            }

            return false;
        });
    }

    /**
     * Will proceed the installation for the provided module
     *
     * @param  string  $moduleNamespace
     * @return void
     */
    public function installPackagesFor($moduleNamespace)
    {
        $process = new Process([
            ( new ExecutableFinder )->find( env( 'MYNEXOPOS_COMPOSER', 'composer' ), 'composer'),
            'install',
        ], base_path('Modules'.DIRECTORY_SEPARATOR.$moduleNamespace), [
            'COMPOSER_HOME'     =>  ( new ExecutableFinder )->find( env( 'MYNEXOPOS_COMPOSER', 'composer' ), 'composer'),
        ]);

        $process->mustRun();

        return [
            'status'    =>  'success',
            'message'   =>  __m('The vendor has been installed', 'MyNexoPOS'),
        ];
    }
}
