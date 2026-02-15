<?php

namespace Modules\NsMultiStore\Settings;

use App\Classes\FormInput;
use App\Classes\SettingForm;
use App\Services\Helper;
use App\Services\SettingsPage;

class GeneralSettings extends SettingsPage
{
    const AUTOLOAD = true;

    const IDENTIFIER = 'ns.multistore-settings';

    public function __construct()
    {
        $this->form = SettingForm::form(
            title: __m('MultiStore Settings', 'NsMultiStore'),
            description: __m('Configure how your stores network.', 'NsMultiStore'),
            tabs: SettingForm::tabs(
                SettingForm::tab(
                    identifier: 'general',
                    label: __m( 'General', 'NsMultiStore' ),
                    fields: SettingForm::fields(
                        FormInput::switch(
                            label: __m('Sub Domain Routing', 'NsMuliStore'),
                            name: 'nsmultistore-subdomain',
                            value: ns()->option->get('nsmultistore-subdomain'),
                            options: Helper::kvToJsOptions([
                                'enabled'   =>  __m('Enabled', 'NsMultiStore'),
                                'disabled'   =>  __m('Disabled', 'NsMultiStore'),
                            ]),
                            description: __m('Define if the NexoPOS should use sub domains for detecting sub store.', 'NsMultiStore'),
                        ),
                        FormInput::switch(
                            label: __m('Refresh Stateful Domains', 'NsMuliStore'),
                            name: 'nsmultistore-statefuldomains',
                            value: ns()->option->get('nsmultistore-statefuldomains', 'no'),
                            options: Helper::kvToJsOptions([
                                'yes'   =>  __m('Yes', 'NsMultiStore'),
                                'no'   =>  __m('No', 'NsMultiStore'),
                            ]),
                            description: __m('By enabling this option, the stateful domains will be refreshed. Will be set to "No" after it has refreshed the domains', 'NsMultiStore'),
                        ),
                    )
                )
            )
        );
    }
}
