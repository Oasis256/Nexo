@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body.with-header' )
    @include( 'common.dashboard.title' )
    <mynexopos-license></mynexopos-license>
@endsection

@section( 'layout.dashboard.footer.inject' )
    @parent
    <script>
        const licenses      =   @json( $licenses );
        const redirectUrl   =   `{{ route( 'mynexopos.update' ) }}`;
        const currentURL    =   `{{ url()->current() }}`;
    </script>
    <script type="module">
        nsExtraComponents[ 'mynexopos-license' ]   =   defineComponent({
            template:`
            <div class="flex flex-col w-full md:w-2/5 ns-box shadow">
                <p v-if="licenses.length > 0" class="rounded-lg p-2 text-white bg-info-secondary text-center text-sm m-2 border border-info-tertiary">
                    {{ __( 'You\'re about to assign a license to your installation. Once a license is assigned to an installation, it cannot be used while it\'s activated here. The license is strict to the system address, so multiple license can be applied to the same installation.' ) }}
                </p>
                <p v-if="licenses.length === undefined" class="rounded-lg p-2 bg-error-secondary text-center text-sm m-2 border border-error-tertiary">
                    {{ __( 'Unable to connect to my.nexopos.com. Probably the client has been deleted.' ) }}
                </p>
                <div class="p-2">
                    <ns-field v-for="field of fields" :field="field"></ns-field>
                </div>
                <div class="border-t flex ns-box-footer p-2 justify-between">
                    <div>
                        <ns-button @click="disconnect()" type="danger">{{ __( 'Disconnect' ) }}</ns-button>
                    </div>
                    <div>
                        <ns-button @click="assignTheLicense()" type="info">{{ __( 'Save' ) }}</ns-button>
                    </div>
                </div>
            </div>
            `,
            name: 'mynexopos-license',
            data() {
                return {
                    validation: new FormValidation,
                    licenses: [],
                    fields: [
                        {
                            'label'         :   __m( 'License', 'MyNexoPOS' ),
                            'name'          :   'license_id',
                            'type'          :   'select',
                            'validation'    :   'required',
                            'description'   :   __m( 'Selec the license to apply to the domain.', 'MyNexoPOS' ),
                            'options'       :   licenses.length !== undefined ? licenses.map( license => {
                                return {
                                    label: `${license.name} - (${ license.domain || __m( 'Not Assigned', 'MyNexoPOS' ) })`,
                                    value: license.id
                                }
                            }) : []
                        }
                    ]
                }
            },
            methods: {
                disconnect() {
                    Popup.show( nsConfirmPopup, {
                        title: __m( 'Confirm Your Action', 'MyNexoPOS' ),
                        message: __m( 'Would you like to disconnect from my.nexopos.com ?', 'MyNexoPOS' ),
                        onAction: ( action ) => {
                            nsHttpClient.get( '/api/mns/disconnect' )
                                .subscribe({
                                    next: result => {
                                        nsSnackBar.success( result.message ).subscribe();
                                        
                                        setTimeout( () => {
                                            document.location   =   currentURL;
                                        }, 1000 );
                                    },
                                    error: error => {
                                        nsSnackBar.error( error.message ).subscribe();
                                    }
                                })
                        }
                    })
                },
                async assignTheLicense() {
                    if ( ! this.validation.validateFields( this.fields ) ) {
                        return nsSnackBar.error( __m( 'You must select a license before proceeding.', 'MyNexoPOS' ) )
                            .subscribe();
                    }

                    try {
                        const result        =   await new Promise( ( resolve, reject ) => {
                            const form      =   this.validation.extractFields( this.fields );
                            const license   =   licenses.filter( l => l.id === form.license_id )[0];

                            Popup.show( nsConfirmPopup, {
                                title: __m( 'Confirm Your Action', 'MyNexoPOS' ),
                                message: __m( 'Would you like to assign this installation to the selected license ?', 'MyNexoPOS' ),
                                onAction: ( action ) => {
                                    nsHttpClient.post( '/api/mns/select-license', { license })
                                        .subscribe({
                                            next: result => {
                                                nsSnackBar.success( result.message ).subscribe();
                                                
                                                setTimeout( () => {
                                                    document.location   =   redirectUrl;
                                                }, 1000 );
                                            },
                                            error: error => {
                                                nsSnackBar.error( error.message ).subscribe();
                                            }
                                        })
                                }
                            })
                        })
                    } catch( exception ) {
                        // ...
                    }
                }
            },
            mounted() {
                console.log( this.fields );
            }
        });
    </script>
@endsection