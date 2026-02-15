<?php

namespace Modules\RenCommissions\Settings;

use App\Services\SettingsPage;

class CommissionSettings extends SettingsPage
{
    const IDENTIFIER = 'rencommissions';

    const AUTOLOAD = true;

    public function __construct()
    {
        $this->form = [
            'tabs' => [
                'general' => include __DIR__ . '/commission/general.php',
                'defaults' => include __DIR__ . '/commission/defaults.php',
                'cleanup' => include __DIR__ . '/commission/cleanup.php',
            ],
            'title' => __m('Commission Settings', 'RenCommissions'),
            'description' => __m('Configure per-item commission settings.', 'RenCommissions'),
        ];
    }
}

