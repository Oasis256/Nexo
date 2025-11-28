<?php

namespace Modules\Skeleton\Settings;

use App\Classes\SettingForm;
use App\Classes\FormInput;
use App\Services\SettingsPage;

class SkeletonSettings extends SettingsPage
{
    const IDENTIFIER = 'skeleton';

    public function __construct()
    {
        $this->form = [
            'title' => __('Skeleton Settings'),
            'description' => __('Configure the Skeleton module settings'),
            'tabs' => SettingForm::tabs(
                SettingForm::tab(
                    identifier: 'general',
                    label: __('General'),
                    fields: [
                        FormInput::switch(
                            label: __('Enable Module'),
                            name: 'skeleton_enabled',
                            options: [
                                ['label' => __('Yes'), 'value' => '1'],
                                ['label' => __('No'), 'value' => '0'],
                            ],
                            value: ns()->option->get('skeleton_enabled', '1'),
                            description: __('Enable or disable the Skeleton module.')
                        ),
                        FormInput::text(
                            label: __('Default Category'),
                            name: 'skeleton_default_category',
                            value: ns()->option->get('skeleton_default_category', 'general'),
                            description: __('Set the default category for new items.')
                        ),
                        FormInput::number(
                            label: __('Items Per Page'),
                            name: 'skeleton_items_per_page',
                            value: ns()->option->get('skeleton_items_per_page', '10'),
                            validation: 'required|integer|min:5|max:100',
                            description: __('Number of items to display per page.')
                        ),
                    ]
                ),
                SettingForm::tab(
                    identifier: 'advanced',
                    label: __('Advanced'),
                    fields: [
                        FormInput::switch(
                            label: __('Enable API'),
                            name: 'skeleton_api_enabled',
                            options: [
                                ['label' => __('Yes'), 'value' => '1'],
                                ['label' => __('No'), 'value' => '0'],
                            ],
                            value: ns()->option->get('skeleton_api_enabled', '1'),
                            description: __('Enable or disable API endpoints.')
                        ),
                        FormInput::switch(
                            label: __('Enable Events'),
                            name: 'skeleton_events_enabled',
                            options: [
                                ['label' => __('Yes'), 'value' => '1'],
                                ['label' => __('No'), 'value' => '0'],
                            ],
                            value: ns()->option->get('skeleton_events_enabled', '1'),
                            description: __('Enable or disable event dispatching.')
                        ),
                    ]
                )
            ),
        ];
    }
}
