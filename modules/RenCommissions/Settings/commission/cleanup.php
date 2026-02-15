<?php

use App\Classes\FormInput;
use App\Classes\SettingForm;
use App\Services\Helper;

return SettingForm::tab(
    identifier: 'cleanup',
    label: __m('Session Cleanup', 'RenCommissions'),
    fields: SettingForm::fields(
        FormInput::number(
            label: __m('POS Session TTL (Minutes)', 'RenCommissions'),
            name: 'rencommissions_pos_session_ttl',
            value: ns()->option->get('rencommissions_pos_session_ttl', 180),
            validation: 'nullable|integer|min:5|max:10080',
            description: __m('Temporary assignment sessions older than this value are considered stale.', 'RenCommissions')
        ),
        FormInput::number(
            label: __m('Debug Retention (Days)', 'RenCommissions'),
            name: 'rencommissions_debug_retention_days',
            value: ns()->option->get('rencommissions_debug_retention_days', 7),
            validation: 'nullable|integer|min:1|max:365',
            description: __m('How long internal debug traces should be kept for troubleshooting.', 'RenCommissions')
        ),
        FormInput::switch(
            label: __m('Auto Cleanup Stale Sessions', 'RenCommissions'),
            name: 'rencommissions_auto_cleanup_sessions',
            value: ns()->option->get('rencommissions_auto_cleanup_sessions', 'yes'),
            options: Helper::kvToJsOptions([
                'yes' => __m('Yes', 'RenCommissions'),
                'no' => __m('No', 'RenCommissions'),
            ]),
            description: __m('Allow automatic cleanup of stale commission assignment sessions.', 'RenCommissions')
        )
    )
);

