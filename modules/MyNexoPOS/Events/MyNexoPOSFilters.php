<?php

namespace Modules\MyNexoPOS\Events;

use Modules\MyNexoPOS\Settings\MyNexoPOSSettings;

class MyNexoPOSFilters
{
    public static function dashboardMenus($menus)
    {
        if (isset($menus['dashboard'])) {
            $menus['dashboard']['childrens']['update'] = [
                'label'         =>      __m('Update', 'MyNexoPOS'),
                'href'          =>      route('mynexopos.update'),
                'permissions'   =>      ['mns.update-system'],
                'icon'          =>      'la-sync',
            ];
        }

        if (isset($menus['settings'])) {
            $menus['settings']['childrens']['update'] = [
                'label'         =>      __m('Update Settings', 'MyNexoPOS'),
                'href'          =>      route('ns.dashboard.settings', [
                    'settings'  =>      MyNexoPOSSettings::$namespace,
                ]),
                'permissions'   =>      ['mns.update-system'],
                'icon'          =>      'la-sync',
            ];
        }

        return $menus;
    }
}
