<?php

namespace Modules\Commission\Settings;

use App\Classes\FormInput;
use App\Classes\SettingForm;
use App\Services\SettingsPage;

class CommissionSettings extends SettingsPage
{
    const IDENTIFIER = 'commission.settings';
    const AUTOLOAD = true;

    protected $form;

    /**
     * Define settings labels
     */
    public function __construct()
    {
        $this->form = SettingForm::form(
            title: __m('Commission Settings', 'Commission'),
            description: __m('Configure the commission module settings.', 'Commission'),
            tabs: SettingForm::tabs(
                SettingForm::tab(
                    identifier: 'general',
                    label: __m('General', 'Commission'),
                    fields: $this->getGeneralFields()
                ),
                SettingForm::tab(
                    identifier: 'defaults',
                    label: __m('Defaults', 'Commission'),
                    fields: $this->getDefaultFields()
                ),
                SettingForm::tab(
                    identifier: 'display',
                    label: __m('Display', 'Commission'),
                    fields: $this->getDisplayFields()
                )
            )
        );
    }

    /**
     * Get the settings form configuration
     */
    public function getForm(): array
    {
        return $this->form;
    }

    /**
     * Get general settings fields
     */
    protected function getGeneralFields(): array
    {
        return [
            FormInput::switch(
                label: __m('Enable Commissions', 'Commission'),
                name: 'commission_enabled',
                value: ns()->option->get('commission_enabled', 'yes'),
                options: [
                    ['label' => __m('Yes', 'Commission'), 'value' => 'yes'],
                    ['label' => __m('No', 'Commission'), 'value' => 'no'],
                ],
                description: __m('Enable or disable the commission system.', 'Commission')
            ),
            FormInput::switch(
                label: __m('Auto-assign Commissions', 'Commission'),
                name: 'commission_auto_assign',
                value: ns()->option->get('commission_auto_assign', 'yes'),
                options: [
                    ['label' => __m('Yes', 'Commission'), 'value' => 'yes'],
                    ['label' => __m('No', 'Commission'), 'value' => 'no'],
                ],
                description: __m('Automatically assign commissions to order author if not selected in POS.', 'Commission')
            ),
            FormInput::switch(
                label: __m('Show Commission in POS', 'Commission'),
                name: 'commission_show_in_pos',
                value: ns()->option->get('commission_show_in_pos', 'yes'),
                options: [
                    ['label' => __m('Yes', 'Commission'), 'value' => 'yes'],
                    ['label' => __m('No', 'Commission'), 'value' => 'no'],
                ],
                description: __m('Show commission selection popup when adding products to cart.', 'Commission')
            ),
            FormInput::switch(
                label: __m('Process on Partial Payment', 'Commission'),
                name: 'commission_on_partial',
                value: ns()->option->get('commission_on_partial', 'no'),
                options: [
                    ['label' => __m('Yes', 'Commission'), 'value' => 'yes'],
                    ['label' => __m('No', 'Commission'), 'value' => 'no'],
                ],
                description: __m('Calculate commissions even if order is only partially paid.', 'Commission')
            ),
        ];
    }

    /**
     * Get default settings fields
     */
    protected function getDefaultFields(): array
    {
        return [
            FormInput::select(
                label: __m('Default Commission Type', 'Commission'),
                name: 'commission_default_type',
                value: ns()->option->get('commission_default_type', 'percentage'),
                options: [
                    ['label' => __m('Percentage', 'Commission'), 'value' => 'percentage'],
                    ['label' => __m('Fixed Amount', 'Commission'), 'value' => 'fixed'],
                    ['label' => __m('On The House', 'Commission'), 'value' => 'on_the_house'],
                ],
                description: __m('Default commission type when creating new commissions.', 'Commission')
            ),
            FormInput::select(
                label: __m('Default Calculation Base', 'Commission'),
                name: 'commission_default_base',
                value: ns()->option->get('commission_default_base', 'net'),
                options: [
                    ['label' => __m('Net Sales (after discounts)', 'Commission'), 'value' => 'net'],
                    ['label' => __m('Gross Sales (before discounts)', 'Commission'), 'value' => 'gross'],
                    ['label' => __m('Fixed Amount', 'Commission'), 'value' => 'fixed'],
                ],
                description: __m('Default calculation base for percentage commissions.', 'Commission')
            ),
            FormInput::number(
                label: __m('Default Commission Value', 'Commission'),
                name: 'commission_default_value',
                value: ns()->option->get('commission_default_value', '5'),
                validation: 'required|numeric|min:0',
                description: __m('Default commission value (percentage or amount).', 'Commission')
            ),
        ];
    }

    /**
     * Get display settings fields
     */
    protected function getDisplayFields(): array
    {
        return [
            FormInput::switch(
                label: __m('Show on Receipt', 'Commission'),
                name: 'commission_show_on_receipt',
                value: ns()->option->get('commission_show_on_receipt', 'no'),
                options: [
                    ['label' => __m('Yes', 'Commission'), 'value' => 'yes'],
                    ['label' => __m('No', 'Commission'), 'value' => 'no'],
                ],
                description: __m('Display commission information on printed receipts.', 'Commission')
            ),
            FormInput::number(
                label: __m('Dashboard Widget Limit', 'Commission'),
                name: 'commission_widget_limit',
                value: ns()->option->get('commission_widget_limit', '5'),
                validation: 'required|integer|min:1|max:20',
                description: __m('Number of items to display in dashboard widgets.', 'Commission')
            ),
            FormInput::select(
                label: __m('Default Report Period', 'Commission'),
                name: 'commission_report_period',
                value: ns()->option->get('commission_report_period', 'month'),
                options: [
                    ['label' => __m('Current Day', 'Commission'), 'value' => 'day'],
                    ['label' => __m('Current Week', 'Commission'), 'value' => 'week'],
                    ['label' => __m('Current Month', 'Commission'), 'value' => 'month'],
                    ['label' => __m('Current Year', 'Commission'), 'value' => 'year'],
                ],
                description: __m('Default date range for commission reports.', 'Commission')
            ),
        ];
    }
}
