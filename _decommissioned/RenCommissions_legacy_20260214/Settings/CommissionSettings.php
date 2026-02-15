<?php

namespace Modules\RenCommissions\Settings;

use App\Services\SettingsPage;

/**
 * Commission Settings Page
 */
class CommissionSettings extends SettingsPage
{
    const IDENTIFIER = 'rencommissions';

    const AUTOLOAD = true;

    public $form;

    public function __construct()
    {
        $this->form = [
            'tabs' => [
                'general' => include dirname(__FILE__) . '/commission/general.php',
                'defaults' => include dirname(__FILE__) . '/commission/defaults.php',
                'cleanup' => include dirname(__FILE__) . '/commission/cleanup.php',
            ],
            'title' => __m('Commission Settings', 'RenCommissions'),
            'description' => __m('Configure per-item commission settings.', 'RenCommissions'),
        ];
    }
}
