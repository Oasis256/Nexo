<?php

use App\Classes\FormInput;

/**
 * Session cleanup settings tab
 */

return [
    'label' => __m('Session Cleanup', 'RenCommissions'),
    'fields' => [
        FormInput::number(
            label: __m('Session Expiration Hours', 'RenCommissions'),
            name: 'rencommissions_session_expiration_hours',
            value: ns()->option->get('rencommissions_session_expiration_hours', 24),
            validation: 'required|integer|min:1|max:168',
            description: __m('Number of hours after which session commission records are considered expired and will be cleaned up.', 'RenCommissions')
        ),

        FormInput::switch(
            label: __m('Auto Cleanup', 'RenCommissions'),
            name: 'rencommissions_auto_cleanup',
            options: [
                ['label' => __m('Yes', 'RenCommissions'), 'value' => 'yes'],
                ['label' => __m('No', 'RenCommissions'), 'value' => 'no'],
            ],
            value: ns()->option->get('rencommissions_auto_cleanup', 'yes'),
            description: __m('Automatically run cleanup during scheduled tasks.', 'RenCommissions')
        ),

        FormInput::select(
            label: __m('Cleanup Frequency', 'RenCommissions'),
            name: 'rencommissions_cleanup_frequency',
            options: [
                ['label' => __m('Hourly', 'RenCommissions'), 'value' => 'hourly'],
                ['label' => __m('Daily', 'RenCommissions'), 'value' => 'daily'],
                ['label' => __m('Weekly', 'RenCommissions'), 'value' => 'weekly'],
            ],
            value: ns()->option->get('rencommissions_cleanup_frequency', 'daily'),
            description: __m('How often the automatic cleanup should run.', 'RenCommissions')
        ),
    ],
];
