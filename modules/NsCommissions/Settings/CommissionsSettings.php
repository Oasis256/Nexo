<?php

namespace Modules\NsCommissions\Settings;

use App\Models\Role;
use App\Services\Helper;
use App\Services\SettingsPage;

class CommissionsSettings extends SettingsPage
{
    const IDENTIFIER = 'ns.commissions-settings';

    protected $form = [];

    public function __construct()
    {
        $this->labels = [
            'title'       =>  __m('Commissions Settings', 'NsCommissions'),
            'description' =>  __m('Configure how the commissions module works.', 'NsCommissions'),
        ];

        $this->identifier = 'ns.commissions-settings';

        $this->form = [
            'tabs'      =>  [
                'general'   =>  [
                    'label'     =>  __m('General', 'NsCommissions'),
                    'fields'    =>  [
                        [
                            'type'          =>  'multiselect',
                            'name'          =>  'ns_commissions_roles',
                            'value'         =>  ns()->option->get('ns_commissions_roles'),
                            'label'         =>  __m('Tracked Roles', 'NsCommissions'),
                            'description'   =>  __m('Select which roles should be tracked by the module.', 'NsCommissions'),
                            'options'       =>  Helper::toJsOptions(Role::get(), ['id', 'name']),
                            'validation'    =>  'required',
                        ],
                        [
                            'type'          =>  'switch',
                            'name'          =>  'ns_commissions_enabled',
                            'value'         =>  ns()->option->get('ns_commissions_enabled', 'yes'),
                            'label'         =>  __m('Enable Commissions', 'NsCommissions'),
                            'description'   =>  __m('Enable or disable commission tracking globally.', 'NsCommissions'),
                            'options'       =>  Helper::kvToJsOptions([
                                'yes'   =>  __m('Yes', 'NsCommissions'),
                                'no'    =>  __m('No', 'NsCommissions'),
                            ]),
                        ],
                    ],
                ],
                'pos_integration'   =>  [
                    'label'     =>  __m('POS Integration', 'NsCommissions'),
                    'fields'    =>  [
                        [
                            'type'          =>  'switch',
                            'name'          =>  'ns_commissions_pos_selection',
                            'value'         =>  ns()->option->get('ns_commissions_pos_selection', 'no'),
                            'label'         =>  __m('Enable POS User Selection', 'NsCommissions'),
                            'description'   =>  __m('When enabled, a popup will appear when adding products to cart, allowing selection of which user earns the commission.', 'NsCommissions'),
                            'options'       =>  Helper::kvToJsOptions([
                                'yes'   =>  __m('Yes', 'NsCommissions'),
                                'no'    =>  __m('No', 'NsCommissions'),
                            ]),
                        ],
                        [
                            'type'          =>  'switch',
                            'name'          =>  'ns_commissions_show_preview',
                            'value'         =>  ns()->option->get('ns_commissions_show_preview', 'yes'),
                            'label'         =>  __m('Show Commission Preview', 'NsCommissions'),
                            'description'   =>  __m('Display estimated commission amount in the selection popup.', 'NsCommissions'),
                            'options'       =>  Helper::kvToJsOptions([
                                'yes'   =>  __m('Yes', 'NsCommissions'),
                                'no'    =>  __m('No', 'NsCommissions'),
                            ]),
                        ],
                        [
                            'type'          =>  'switch',
                            'name'          =>  'ns_commissions_allow_on_the_house',
                            'value'         =>  ns()->option->get('ns_commissions_allow_on_the_house', 'yes'),
                            'label'         =>  __m('Allow "On The House"', 'NsCommissions'),
                            'description'   =>  __m('Allow selecting "On The House" option which marks the product as no commission payable.', 'NsCommissions'),
                            'options'       =>  Helper::kvToJsOptions([
                                'yes'   =>  __m('Yes', 'NsCommissions'),
                                'no'    =>  __m('No', 'NsCommissions'),
                            ]),
                        ],
                    ],
                ],
            ],
        ];
    }
}
