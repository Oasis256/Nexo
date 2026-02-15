<?php

use App\Classes\FormInput;

/**
 * Default commission values settings tab
 */

return [
    'label' => __m('Default Values', 'RenCommissions'),
    'fields' => [
        FormInput::select(
            label: __m('Default Commission Type', 'RenCommissions'),
            name: 'rencommissions_default_type',
            options: [
                ['label' => __m('Percentage', 'RenCommissions'), 'value' => 'percentage'],
                ['label' => __m('Fixed Amount', 'RenCommissions'), 'value' => 'fixed'],
                ['label' => __m('On-The-House', 'RenCommissions'), 'value' => 'on_the_house'],
            ],
            value: ns()->option->get('rencommissions_default_type', 'percentage'),
            description: __m('The default commission type when assigning commissions.', 'RenCommissions')
        ),

        FormInput::number(
            label: __m('Default Percentage', 'RenCommissions'),
            name: 'rencommissions_default_percentage',
            value: ns()->option->get('rencommissions_default_percentage', 5),
            validation: 'required|numeric|min:0|max:100',
            description: __m('Default percentage rate for percentage-based commissions.', 'RenCommissions')
        ),

        FormInput::number(
            label: __m('On-The-House Amount', 'RenCommissions'),
            name: 'rencommissions_on_the_house_amount',
            value: ns()->option->get('rencommissions_on_the_house_amount', 1),
            validation: 'required|numeric|min:0',
            description: __m('Fixed amount per unit for "On-The-House" commissions.', 'RenCommissions')
        ),

        FormInput::number(
            label: __m('Minimum Percentage', 'RenCommissions'),
            name: 'rencommissions_min_percentage',
            value: ns()->option->get('rencommissions_min_percentage', 0),
            validation: 'required|numeric|min:0|max:100',
            description: __m('Minimum allowed percentage value.', 'RenCommissions')
        ),

        FormInput::number(
            label: __m('Maximum Percentage', 'RenCommissions'),
            name: 'rencommissions_max_percentage',
            value: ns()->option->get('rencommissions_max_percentage', 100),
            validation: 'required|numeric|min:0|max:100',
            description: __m('Maximum allowed percentage value.', 'RenCommissions')
        ),
    ],
];
