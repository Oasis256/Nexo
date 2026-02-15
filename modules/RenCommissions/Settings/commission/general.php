<?php

use App\Classes\FormInput;
use App\Classes\SettingForm;
use App\Models\Role;
use App\Services\Helper;

return SettingForm::tab(
    identifier: 'general',
    label: __m('General', 'RenCommissions'),
    fields: SettingForm::fields(
        FormInput::switch(
            label: __m('Enable Commissions', 'RenCommissions'),
            name: 'rencommissions_enabled',
            value: ns()->option->get('rencommissions_enabled', 'yes'),
            options: Helper::kvToJsOptions([
                'yes' => __m('Yes', 'RenCommissions'),
                'no' => __m('No', 'RenCommissions'),
            ]),
            description: __m('Enable or disable the commission system globally.', 'RenCommissions')
        ),
        FormInput::switch(
            label: __m('Show Commission Button', 'RenCommissions'),
            name: 'rencommissions_show_pos_button',
            value: ns()->option->get('rencommissions_show_pos_button', 'yes'),
            options: Helper::kvToJsOptions([
                'yes' => __m('Yes', 'RenCommissions'),
                'no' => __m('No', 'RenCommissions'),
            ]),
            description: __m('Show the commission assignment button in POS product lines.', 'RenCommissions')
        ),
        FormInput::switch(
            label: __m('Require Commission Assignment', 'RenCommissions'),
            name: 'rencommissions_require_assignment',
            value: ns()->option->get('rencommissions_require_assignment', 'no'),
            options: Helper::kvToJsOptions([
                'yes' => __m('Yes', 'RenCommissions'),
                'no' => __m('No', 'RenCommissions'),
            ]),
            description: __m('If enabled, orders cannot be completed without assigning commissions to all items.', 'RenCommissions')
        ),
        FormInput::multiselect(
            label: __m('Eligible Roles', 'RenCommissions'),
            name: 'rencommissions_eligible_roles',
            value: ns()->option->get('rencommissions_eligible_roles', []),
            options: Role::get()
                ->mapWithKeys(fn ($role) => [ $role->id => $role->name ])
                ->toArray(),
            description: __m('Select which roles can receive commissions. Leave empty to allow all active users.', 'RenCommissions')
        )
    )
);

