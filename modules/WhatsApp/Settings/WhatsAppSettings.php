<?php

namespace Modules\WhatsApp\Settings;

use App\Classes\FormInput;
use App\Classes\SettingForm;
use App\Models\Role;
use App\Services\SettingsPage;

class WhatsAppSettings extends SettingsPage
{
    const IDENTIFIER = 'whatsapp.settings';

    protected $form;

    public function __construct()
    {
        $this->form = $this->buildForm();
    }

    public function getForm(): array
    {
        return $this->form;
    }

    protected function buildForm(): array
    {
        return SettingForm::form(
            title: __m('WhatsApp Settings', 'WhatsApp'),
            description: __m('Configure WhatsApp Business API integration for notifications and communications.', 'WhatsApp'),
            tabs: SettingForm::tabs(
                $this->getGeneralTab(),
                $this->getApiTab(),
                $this->getNotificationsTab(),
                $this->getStaffAlertsTab()
            )
        );
    }

    protected function getGeneralTab(): array
    {
        return SettingForm::tab(
            identifier: 'general',
            label: __m('General', 'WhatsApp'),
            fields: [
                FormInput::switch(
                    label: __m('Enable WhatsApp', 'WhatsApp'),
                    name: 'whatsapp_enabled',
                    value: ns()->option->get('whatsapp_enabled', 'no'),
                    options: [
                        ['label' => __('Yes'), 'value' => 'yes'],
                        ['label' => __('No'), 'value' => 'no'],
                    ],
                    description: __m('Enable or disable WhatsApp integration globally.', 'WhatsApp')
                ),
                FormInput::text(
                    label: __m('Default Country Code', 'WhatsApp'),
                    name: 'whatsapp_default_country_code',
                    value: ns()->option->get('whatsapp_default_country_code', '+1'),
                    description: __m('Default country code for phone numbers without one (e.g., +1, +44, +254).', 'WhatsApp'),
                    validation: 'required'
                ),
            ]
        );
    }

    protected function getApiTab(): array
    {
        return SettingForm::tab(
            identifier: 'api',
            label: __m('API Configuration', 'WhatsApp'),
            fields: [
                FormInput::text(
                    label: __m('Access Token', 'WhatsApp'),
                    name: 'whatsapp_access_token',
                    value: ns()->option->get('whatsapp_access_token', ''),
                    description: __m('Your Meta WhatsApp Business API access token. Get this from the Meta Developer Portal.', 'WhatsApp'),
                    validation: 'required'
                ),
                FormInput::text(
                    label: __m('Phone Number ID', 'WhatsApp'),
                    name: 'whatsapp_phone_number_id',
                    value: ns()->option->get('whatsapp_phone_number_id', ''),
                    description: __m('The Phone Number ID from your WhatsApp Business Account.', 'WhatsApp'),
                    validation: 'required'
                ),
                FormInput::text(
                    label: __m('Business Account ID', 'WhatsApp'),
                    name: 'whatsapp_business_id',
                    value: ns()->option->get('whatsapp_business_id', ''),
                    description: __m('Your WhatsApp Business Account ID (optional, used for advanced features).', 'WhatsApp')
                ),
                FormInput::text(
                    label: __m('Webhook Verify Token', 'WhatsApp'),
                    name: 'whatsapp_webhook_verify_token',
                    value: ns()->option->get('whatsapp_webhook_verify_token', bin2hex(random_bytes(16))),
                    description: __m('Token used to verify webhook requests from WhatsApp. Set this same value in the Meta Developer Portal.', 'WhatsApp')
                ),
            ],
            notices: [
                [
                    'type' => 'info',
                    'message' => __m('To get your API credentials, visit the Meta Developer Portal at developers.facebook.com and set up a WhatsApp Business API application.', 'WhatsApp'),
                ],
            ]
        );
    }

    protected function getNotificationsTab(): array
    {
        return SettingForm::tab(
            identifier: 'notifications',
            label: __m('Customer Notifications', 'WhatsApp'),
            fields: [
                FormInput::switch(
                    label: __m('Order Confirmation', 'WhatsApp'),
                    name: 'whatsapp_send_order_confirmation',
                    value: ns()->option->get('whatsapp_send_order_confirmation', 'yes'),
                    options: [
                        ['label' => __('Yes'), 'value' => 'yes'],
                        ['label' => __('No'), 'value' => 'no'],
                    ],
                    description: __m('Send WhatsApp message when a new order is created.', 'WhatsApp')
                ),
                FormInput::switch(
                    label: __m('Payment Receipt', 'WhatsApp'),
                    name: 'whatsapp_send_payment_receipt',
                    value: ns()->option->get('whatsapp_send_payment_receipt', 'yes'),
                    options: [
                        ['label' => __('Yes'), 'value' => 'yes'],
                        ['label' => __('No'), 'value' => 'no'],
                    ],
                    description: __m('Send WhatsApp message when a payment is received.', 'WhatsApp')
                ),
                FormInput::switch(
                    label: __m('Refund Notification', 'WhatsApp'),
                    name: 'whatsapp_send_refund_notification',
                    value: ns()->option->get('whatsapp_send_refund_notification', 'yes'),
                    options: [
                        ['label' => __('Yes'), 'value' => 'yes'],
                        ['label' => __('No'), 'value' => 'no'],
                    ],
                    description: __m('Send WhatsApp message when a refund is processed.', 'WhatsApp')
                ),
                FormInput::switch(
                    label: __m('Delivery Updates', 'WhatsApp'),
                    name: 'whatsapp_send_delivery_updates',
                    value: ns()->option->get('whatsapp_send_delivery_updates', 'yes'),
                    options: [
                        ['label' => __('Yes'), 'value' => 'yes'],
                        ['label' => __('No'), 'value' => 'no'],
                    ],
                    description: __m('Send WhatsApp message when delivery status changes.', 'WhatsApp')
                ),
                FormInput::switch(
                    label: __m('Welcome Message', 'WhatsApp'),
                    name: 'whatsapp_send_welcome_message',
                    value: ns()->option->get('whatsapp_send_welcome_message', 'yes'),
                    options: [
                        ['label' => __('Yes'), 'value' => 'yes'],
                        ['label' => __('No'), 'value' => 'no'],
                    ],
                    description: __m('Send welcome message when a new customer registers.', 'WhatsApp')
                ),
                FormInput::switch(
                    label: __m('Payment Reminders', 'WhatsApp'),
                    name: 'whatsapp_send_payment_reminders',
                    value: ns()->option->get('whatsapp_send_payment_reminders', 'no'),
                    options: [
                        ['label' => __('Yes'), 'value' => 'yes'],
                        ['label' => __('No'), 'value' => 'no'],
                    ],
                    description: __m('Send payment reminder for due orders.', 'WhatsApp')
                ),
            ]
        );
    }

    protected function getStaffAlertsTab(): array
    {
        // Get available roles for multiselect
        $roles = Role::all()->map(function ($role) {
            return [
                'label' => $role->name,
                'value' => $role->namespace,
            ];
        })->toArray();

        return SettingForm::tab(
            identifier: 'staff_alerts',
            label: __m('Staff Alerts', 'WhatsApp'),
            fields: [
                FormInput::switch(
                    label: __m('Low Stock Alerts', 'WhatsApp'),
                    name: 'whatsapp_send_low_stock_alerts',
                    value: ns()->option->get('whatsapp_send_low_stock_alerts', 'no'),
                    options: [
                        ['label' => __('Yes'), 'value' => 'yes'],
                        ['label' => __('No'), 'value' => 'no'],
                    ],
                    description: __m('Send WhatsApp alerts when products are running low on stock.', 'WhatsApp')
                ),
                FormInput::multiselect(
                    label: __m('Staff Alert Roles', 'WhatsApp'),
                    name: 'whatsapp_staff_alert_roles',
                    value: ns()->option->get('whatsapp_staff_alert_roles', ['admin']),
                    options: $roles,
                    description: __m('Select which roles should receive staff alerts via WhatsApp.', 'WhatsApp')
                ),
            ],
            notices: [
                [
                    'type' => 'warning',
                    'message' => __m('Staff members must have a phone number configured in their profile to receive WhatsApp alerts.', 'WhatsApp'),
                ],
            ]
        );
    }
}
