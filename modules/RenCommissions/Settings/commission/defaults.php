<?php

use App\Classes\FormInput;
use App\Classes\SettingForm;
use App\Services\Helper;
use Modules\RenCommissions\Models\CommissionType;

$types = CommissionType::query()
    ->where('is_active', true)
    ->orderBy('priority')
    ->get()
    ->mapWithKeys(fn ($type) => [ $type->id => $type->name ])
    ->toArray();

return SettingForm::tab(
    identifier: 'defaults',
    label: __m('Default Values', 'RenCommissions'),
    fields: SettingForm::fields(
        FormInput::switch(
            label: __m('Use Product Default Commission Value', 'RenCommissions'),
            name: 'rencommissions_use_product_default',
            value: ns()->option->get('rencommissions_use_product_default', 'yes'),
            options: Helper::kvToJsOptions([
                'yes' => __m('Yes', 'RenCommissions'),
                'no' => __m('No', 'RenCommissions'),
            ]),
            description: __m('When assigning from POS, use the product commission_value by default.', 'RenCommissions')
        ),
        FormInput::select(
            label: __m('Fallback Commission Type', 'RenCommissions'),
            name: 'rencommissions_fallback_type_id',
            options: $types,
            value: (string) ns()->option->get('rencommissions_fallback_type_id', ''),
            description: __m('Used when no specific commission type is chosen during assignment.', 'RenCommissions')
        ),
        FormInput::number(
            label: __m('Default Fixed Amount', 'RenCommissions'),
            name: 'rencommissions_default_fixed_amount',
            value: ns()->option->get('rencommissions_default_fixed_amount', 0),
            validation: 'nullable|numeric|min:0',
            description: __m('Default fixed amount if fixed commission is used without explicit value.', 'RenCommissions')
        ),
        FormInput::number(
            label: __m('Default Percent', 'RenCommissions'),
            name: 'rencommissions_default_percent',
            value: ns()->option->get('rencommissions_default_percent', 0),
            validation: 'nullable|numeric|min:0|max:100',
            description: __m('Default percentage if percent commission is used without explicit value.', 'RenCommissions')
        )
    )
);

