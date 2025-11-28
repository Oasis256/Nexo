<?php

namespace Modules\MyNexoPOS\Settings;

use App\Classes\FormInput;
use App\Classes\Hook;
use App\Classes\Output;
use App\Classes\SettingForm;
use App\Services\SettingsPage;

class MyNexoPOSSettings extends SettingsPage
{
    protected $identifier;

    protected $labels;

    protected $form;

    const IDENTIFIER = 'mynexopos.settings';

    const AUTOLOAD = true;

    public static $namespace = 'mynexopos.settings';

    public function __construct()
    {
        $this->form     =   SettingForm::form(
            title: __m( 'My NexoPOS Settings', 'MyNexoPOS' ),
            description: __m( 'Configure the connectivity with my.nexopos.com', 'MyNexoPOS' ),
            tabs: SettingForm::tabs(
                SettingForm::tab(
                    identifier: 'general',
                    label: __m( 'General', 'MyNexoPOS' ),
                    fields: SettingForm::fields(
                        FormInput::text(
                            name: 'mynexopos_app_id',
                            label: __m( 'APP ID', 'MyNexoPOS' ),
                            description: __m( 'Provide the application ID as provided while creating the Client.', 'MyNexoPOS' ),
                            value: ns()->option->get('mynexopos_app_id'),
                        ),
                        FormInput::password(
                            name: 'mynexopos_secret_key',
                            label: __m( 'Secret Key', 'MyNexoPOS' ),
                            description: __m( 'Provide the Secret Key as provided while creating the Client.', 'MyNexoPOS' ),
                            value: ns()->option->get('mynexopos_secret_key'),
                        ),
                    )
                ),
                SettingForm::tab(
                    identifier: 'executable',
                    label: __m( 'Advanced', 'MyNexoPOS' ),
                    fields: SettingForm::fields(
                        FormInput::text(
                            name: 'mynexopos_php_path',
                            label: __m( 'PHP Executable Path', 'MyNexoPOS' ),
                            description: __m( 'The path to the php executable.', 'MyNexoPOS' ),
                            value: ns()->option->get('mynexopos_php_path'),
                        ),
                        FormInput::password(
                            name: 'mynexopos_composer_path',
                            label: __m( 'Composer Executable Path', 'MyNexoPOS' ),
                            description: __m( 'The path to the composer executable.', 'MyNexoPOS' ),
                            value: ns()->option->get('mynexopos_composer_path'),
                        ),
                    )
                )
            )
        );
    }
}
