<?php

/**
 * My NexoPOS Controller
 *
 * @since  1.0
 **/

namespace Modules\MyNexoPOS\Http\Controllers;

use App\Exceptions\NotAllowedException;
use App\Http\Controllers\DashboardController;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\MyNexoPOS\Services\UpdateService;
use Modules\MyNexoPOS\Settings\MyNexoPOSSettings;

class MyNexoPOSController extends DashboardController
{
    public function updaterPage()
    {
        return View::make('MyNexoPOS::pages.updater', [
            'title'         =>  __m('Update Center', 'MyNexoPOS'),
            'description'   =>  __m('Manage NexoPOS Updates.', 'MyNexoPOS'),
        ]);
    }

    public function authentify()
    {
        if (empty(ns()->option->get('mynexopos_app_id'))) {
            return redirect(ns()->route('ns.dashboard.settings', [
                'settings'  =>  MyNexoPOSSettings::$namespace,
            ]))->with('errorMessage', __m('You need to define App ID and Secret Key.'));
        }

        return View::make('MyNexoPOS::pages.authentify', [
            'title'         =>  __m('Authentify With My NexoPOS', 'MyNexoPOS'),
            'description'   =>  __m('Link your account on my.nexopos.com.', 'MyNexoPOS'),
        ]);
    }

    public function selectLicense()
    {
        $licenses = Http::withHeaders([
            'Accept'        => 'application/json',
            'Authorization' => 'Bearer '.ns()->option->get('mynexopos_access_token'),
        ])->get('https://my.nexopos.com/api/user/licenses');

        return View::make('MyNexoPOS::pages.license', [
            'title'         =>  __m('Assign A License', 'MyNexoPOS'),
            'description'   =>  __m('Assign a license to your installation.', 'MyNexoPOS'),
            'licenses'      =>  collect( $licenses->object() )->filter( fn( $license ) => $license->item_id == 31188619 )->values(),
        ]);
    }

    public function verifyAuthentication(Request $request)
    {
        $state = $request->session()->pull('state');

        throw_unless(
            strlen($state) > 0 && $state === $request->state,
            InvalidArgumentException::class
        );

        $response = Http::asForm()->post('https://my.nexopos.com/oauth/token', [
            'grant_type'        => 'authorization_code',
            'client_id'         => ns()->option->get('mynexopos_app_id'),
            'client_secret'     => ns()->option->get('mynexopos_secret_key'),
            'redirect_uri'      => route('mynexopos.verify-authentication'),
            'code'              => $request->code,
        ]);

        $result = $response->object();

        ns()->option->set('mynexopos_access_token', $result->access_token);
        ns()->option->set('mynexopos_refresh_token', $result->refresh_token);

        return redirect(route('mynexopos.select-license'));
    }

    public function requestToken(Request $request)
    {
        $request->session()->put('state', $state = Str::random(40));

        $query = http_build_query([
            'client_id'     => ns()->option->get('mynexopos_app_id'),
            'redirect_uri'  => route('mynexopos.verify-authentication'),
            'response_type' => 'code',
            'scope'         => 'use-downloads read-profile read-licenses update-licenses',
            'state'         => $state,
        ]);

        return redirect('https://my.nexopos.com/oauth/authorize?'.$query);
    }

    public function applySelectedLicense(Request $request)
    {
        try {
            $domain = parse_url(url('/'));
            $result = Http::withHeaders([
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer '.ns()->option->get('mynexopos_access_token'),
            ])->post('https://my.nexopos.com/api/user/verify-licenses', [
                'license'       =>  $request->input('license'),
                'reference'     =>  31188619,
                'domain'        =>  $domain['host'].($domain['path'] ?? ''),
            ]);

            if (! isset($result->object()->status) || $result->object()->status !== 'success') {
                throw new Exception($result->object()->message);
            }

            ns()->option->set('mynexopos_license', $request->input('license')['license']);

            return [
                'status'    =>  'success',
                'message'   =>  $result->object()->message,
            ];
        } catch (Exception $exception) {
            return response()->json([
                'status'    =>  'error',
                'message'   =>  $exception->getMessage(),
            ], 403);
        }
    }

    public function deactivateLicense(Request $request)
    {
        try {
            $domain = parse_url(url('/'));
            $result = Http::withHeaders([
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer '.ns()->option->get('mynexopos_access_token'),
            ])->post('https://my.nexopos.com/api/user/license-deactivation/'.ns()->option->get('mynexopos_license'), [
                'reference'     =>  31188619,
                'domain'        =>  $domain['host'].($domain['path'] ?? ''),
            ]);

            if (! isset($result->object()->status) || $result->object()->status !== 'success') {
                throw new Exception($result->object()->message);
            }

            ns()->option->delete('mynexopos_license');

            return redirect(route('mynexopos.select-license'))->with('message', $result->object()->message);
        } catch (Exception $exception) {
            ns()->option->delete('mynexopos_license');

            return redirect(route('mynexopos.select-license'))->with('message', $result->object()->message);
        }
    }

    public function proceedCoreUpdate(Request $request)
    {
        $service = new UpdateService;

        return $service->updateTo($request->input('release'));
    }

    public function refreshToken()
    {
        $response = Http::asForm()->post('https://my.nexopos.com/oauth/token', [
            'grant_type'        => 'refresh_token',
            'refresh_token'     => ns()->option->get('mynexopos_refresh_token'),
            'client_id'         => ns()->option->get('mynexopos_app_id'),
            'client_secret'     => ns()->option->get('mynexopos_secret_key'),
            'scope'             => 'use-downloads read-profile read-licenses update-licenses',
        ]);

        if ($response->status() === 403) {
            throw new Exception($response->body());
        }

        $tokens = $response->object();

        ns()->option->set('mynexopos_access_token', $tokens->access_token);
        ns()->option->set('mynexopos_refresh_token', $tokens->refresh_token);
    }

    /**
     * Will return the actual latest release for the core
     *
     * @param  Request  $request
     * @return string json
     */
    public function getCoreLatestRelease(Request $request)
    {
        try {
            $result = Http::withHeaders([
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer '.ns()->option->get('mynexopos_access_token'),
            ])->get('https://my.nexopos.com/api/core/latest-release');

            if (isset($result->object()->message) && $result->object()->message === 'Unauthenticated.') {
                try {
                    $this->refreshToken();
                } catch (Exception $exception) {
                    throw new Exception(__m('You\'re not authenticated.', 'MyNexoPOS'));
                }
            }

            if (! isset($result->object()->version)) {
                return [
                    'status'    =>  'info',
                    'message'   =>  __m('There is no update to display at the moment', 'MyNexoPOS'),
                ];
            }

            $release = $result->object();
            $realVersion = substr($release->version, 1);

            if (version_compare($realVersion, config('nexopos.version'), '>')) {
                return $result->json();
            }

            return [
                'status'    =>  'info',
                'message'   =>  __m('The system is already up to date.', 'MyNexoPOS'),
            ];
        } catch (Exception $exception) {
            return response()->json([
                'status'    =>  'error',
                'message'   =>  $exception->getMessage(),
            ], 403);
        }
    }

    /**
     * Will clear all token from
     * the platform
     *
     * @return array
     */
    public function disconnect()
    {
        ns()->option->delete('mynexopos_refresh_token');
        ns()->option->delete('mynexopos_access_token');

        return [
            'status'    =>  'success',
            'message'   =>  __m('Disconnected from my.nexopos.com', 'MyNexoPOS'),
        ];
    }

    public function installModulesVendor()
    {
        /**
         * @var UpdateService
         */
        $updateService = app()->make(UpdateService::class);

        return View::make('MyNexoPOS::pages.vendor', [
            'title'     =>  __m('Module Vendor Installation', 'MyNexoPOS'),
            'modules'   =>  $updateService->getModulesRequiringComposer()->values(),
        ]);
    }

    public function installVendor(Request $request)
    {
        if ($request->input('module')) {
            $module = $request->input('module');

            /**
             * @var UpdateService
             */
            $updateService = app()->make(UpdateService::class);

            return $updateService->installPackagesFor($module['namespace']);
        }

        throw new NotAllowedException(__m('Invalid Form Request.', 'MyNexoPOS'));
    }
}
