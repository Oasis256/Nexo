<?php

use App\Classes\FormInput;
use App\Models\Role;
use App\Services\Helper;

/**
 * General commission settings tab
 */

return [
    'label' => __m('General', 'RenCommissions'),
    'fields' => [
        FormInput::switch(
            label: __m('Enable Commissions', 'RenCommissions'),
            name: 'rencommissions_enabled',
            options: [
                ['label' => __m('Yes', 'RenCommissions'), 'value' => 'yes'],
                ['label' => __m('No', 'RenCommissions'), 'value' => 'no'],
            ],
            value: ns()->option->get('rencommissions_enabled', 'yes'),
            description: __m('Enable or disable the commission system globally.', 'RenCommissions')
        ),

        FormInput::switch(
            label: __m('Show Commission Button', 'RenCommissions'),
            name: 'rencommissions_show_button',
            options: [
                ['label' => __m('Yes', 'RenCommissions'), 'value' => 'yes'],
                ['label' => __m('No', 'RenCommissions'), 'value' => 'no'],
            ],
            value: ns()->option->get('rencommissions_show_button', 'yes'),
            description: __m('Show the commission assignment button in POS product lines.', 'RenCommissions')
        ),

        FormInput::multiselect(
            label: __m('Eligible Roles', 'RenCommissions'),
            name: 'rencommissions_eligible_roles',
            options: Helper::toJsOptions(
                Role::where('locked', false)->orWhere('namespace', 'like', 'nexopos.store.%')->get(),
                ['namespace', 'name']
            ),
            value: ns()->option->get('rencommissions_eligible_roles', []),
            description: __m('Select which roles can receive commissions. Leave empty to allow all active users.', 'RenCommissions')
        ),

        FormInput::switch(
            label: __m('Require Commission Assignment', 'RenCommissions'),
            name: 'rencommissions_required',
            options: [
                ['label' => __m('Yes', 'RenCommissions'), 'value' => 'yes'],
                ['label' => __m('No', 'RenCommissions'), 'value' => 'no'],
            ],
            value: ns()->option->get('rencommissions_required', 'no'),
            description: __m('If enabled, orders cannot be completed without assigning commissions to all items.', 'RenCommissions')
        ),
    ],
];
