<?php

namespace Modules\WhatsApp\Filters;

use App\Models\Menu;
use Modules\WhatsApp\Crud\MessageLogCrud;
use Modules\WhatsApp\Crud\MessageTemplateCrud;
use Modules\WhatsApp\Settings\WhatsAppSettings;

class WhatsAppFilters
{
    /**
     * Register WhatsApp CRUD resources
     */
    public static function registerCrud(string $identifier): string
    {
        return match ($identifier) {
            'whatsapp.templates' => MessageTemplateCrud::class,
            'whatsapp.logs' => MessageLogCrud::class,
            default => $identifier,
        };
    }

    /**
     * Add WhatsApp menu to dashboard
     */
    public static function dashboardMenus(array $menus): array
    {
        // Find the position to insert (after "customers" or at the end of main section)
        $whatsappMenu = [
            'whatsapp' => [
                'label' => __m('WhatsApp', 'WhatsApp'),
                'icon' => 'la-whatsapp',
                'permissions' => ['whatsapp.dashboard'],
                'childrens' => [
                    'dashboard' => [
                        'label' => __m('Dashboard', 'WhatsApp'),
                        'href' => ns()->route('whatsapp.dashboard'),
                        'permissions' => ['whatsapp.dashboard'],
                        'icon' => 'la-chart-line',
                    ],
                    'send' => [
                        'label' => __m('Send Message', 'WhatsApp'),
                        'href' => ns()->route('whatsapp.send'),
                        'permissions' => ['whatsapp.send'],
                        'icon' => 'la-paper-plane',
                    ],
                    'templates' => [
                        'label' => __m('Templates', 'WhatsApp'),
                        'href' => ns()->route('whatsapp.templates'),
                        'permissions' => ['whatsapp.templates'],
                        'icon' => 'la-file-alt',
                    ],
                    'logs' => [
                        'label' => __m('Message Logs', 'WhatsApp'),
                        'href' => ns()->route('whatsapp.logs'),
                        'permissions' => ['whatsapp.logs'],
                        'icon' => 'la-history',
                    ],
                    'settings' => [
                        'label' => __m('Settings', 'WhatsApp'),
                        'href' => ns()->route('ns.dashboard.settings', ['settings' => WhatsAppSettings::IDENTIFIER]),
                        'permissions' => ['whatsapp.settings'],
                        'icon' => 'la-cog',
                    ],
                ],
            ],
        ];

        // Insert after customers menu if it exists
        $newMenus = [];
        $inserted = false;

        foreach ($menus as $key => $menu) {
            $newMenus[$key] = $menu;
            if ($key === 'customers' && !$inserted) {
                $newMenus = array_merge($newMenus, $whatsappMenu);
                $inserted = true;
            }
        }

        // If customers menu wasn't found, just append
        if (!$inserted) {
            $newMenus = array_merge($newMenus, $whatsappMenu);
        }

        return $newMenus;
    }

    /**
     * Register WhatsApp settings page
     */
    public static function settingsPages($class, string $identifier)
    {
        if ($identifier === WhatsAppSettings::IDENTIFIER) {
            return new WhatsAppSettings();
        }

        return $class;
    }
}
