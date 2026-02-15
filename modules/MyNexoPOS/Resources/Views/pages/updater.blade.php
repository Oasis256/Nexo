@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body.with-header' )
    @include( 'common.dashboard.title' )
    <mynexopos-updater>
    </mynexopos-updater>
@endsection

@section( 'layout.dashboard.footer.inject' )
    @parent
    <script>
        const deactivateURL     =   `{{ route( 'mynexopos.deactivate-license' ) }}`;
        const currentURL        =   `{{ url()->current() }}`;
    </script>
    <script type="module">
        nsExtraComponents[ 'mynexopos-updater' ]    =   defineComponent({
            name: 'mynexopos-updater',
            template: `
            <div>
                <div class="rounded flex justify-between items-center border border-info-tertiary bg-info-secondary p-2 mb-4">
                    <h3 class="font-semibold text-white">{{ __m( 'Thank you for having validated your license.', 'MyNexoPOS' ) }}</h3>
                    <div>
                        <ns-button @click="confirmDeactivate()" type="danger">{{ __m( 'Deactivate The License', 'MyNexoPOS' ) }}</ns-button>
                    </div>
                </div>
                <div class="shadow ns-box">
                    <div id="header" class="flex justify-between items-center p-2 border-b ns-box-header">
                        <h3>{{ __m( 'Core Updates', 'MyNexoPOS' ) }}</h3>
                        <div>
                            <span class="px-3">{{ sprintf( __m( 'Current Version : %s', 'MyNexoPOS' ), config( 'nexopos.version', 'MyNexoPOS' ) ) }}</span>
                            <ns-icon-button @click="loadUpdate()" class-name="la-sync-alt"></ns-icon-button>
                        </div>
                    </div>
                    <div class="flex flex-col">
                        <div class="update-item border-b ns-box-body flex" v-if="currentUpdate.status !== 'info' && loadingLatestCoreUpdate === false">
                            <div class="p-2 flex-auto">
                                <h4 class="font-semibold">{{ __m( 'NexoPOS', 'MyNexoPOS' ) }} @{{ currentUpdate.version }}</h4>
                                <p class="text-gray-700">{{ __m( 'Click here to see the full changelog of this release', 'MyNexoPOS' ) }}. <a target="_blank" :href="currentUpdate.release_url || currentUpdate.github_url" class="text-blue-400 hover:underline text-sm">[Read More]</a></p>
                                <ul>
                                    <li class="text-xs text-gray-600">{{ __m( 'Version:', 'MyNexoPOS' ) }} @{{ currentUpdate.version }}</li>
                                </ul>
                            </div>
                            <div @click="confirmUpdating()" class="cursor-pointer flex items-center px-2 justify-center border-l border-gray-200 hover:bg-blue-400 hover:text-white">
                                <i :class="isUpdating ? 'animate-spin' : ''" class="las la-sync text-3xl font-bold"></i>
                                <span v-if="! isUpdating" class="ml-1">{{ __m( 'Update Now', 'MyNexoPOS' ) }}</span>
                                <span v-if="isUpdating" class="ml-1">{{ __m( 'Updating...', 'MyNexoPOS' ) }}</span>
                            </div>
                        </div>
                        <div class="update-item justify-center border-b ns-box-body flex py-3" v-if="currentUpdate.status === 'info' && loadingLatestCoreUpdate === false">
                            <h3>@{{ currentUpdate.message }} </h3>
                        </div>
                        <div class="update-item justify-center border-b ns-box-body flex py-3" v-if="loadingLatestCoreUpdate === true && hasError === false">
                            <h3>{{ __m( 'Loading...', 'MyNexoPOS' ) }}</h3>
                        </div>
                        <div class="update-item justify-center border-b ns-box-body flex p-3" v-if="hasError === true">
                            <h3 class="text-center">{{ __m( 'An error has occured while fetching the update. Probably your connexion has expired or the client is no more valid. Please unlink and link your installation to my.nexopos.com', 'MyNexoPOS' ) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
            `,
            mounted() {
                this.loadUpdate();
            },
            data() {
                return {
                    currentUpdate: { info: 'info' },
                    isUpdating: false,
                    loadingLatestCoreUpdate: false,
                    hasError: false,
                }
            },
            methods: {
                loadUpdate() {
                    this.loadLatestCoreRelease()
                        .then( _ => _ )
                        .catch( e => nsSnackBar.error( __( 'An error has occured while fetching the update status.' ) ).subscribe() );
                },

                confirmUpdating() {
                    if ( this.isUpdating ) {
                        return nsSnackBar.error( __( 'An update process is already ongoing' ) ).subscribe();
                    }

                    Popup.show( nsConfirmPopup, {
                        title: __m( 'Confirm Your Action', 'MyNexoPOS' ),
                        message: __m( 'Before updating make sure to have a backup of your file and database. You\'re about to upgrade your system, would you like to proceed ?' , 'MyNexoPOS' ),
                        onAction: ( action ) => {
                            if ( action && this.isUpdating === false ) {
                                this.isUpdating     =   true;
                                nsHttpClient.post( `/api/mns/core/update`, { release : this.currentUpdate })
                                    .subscribe({
                                        next: result => {
                                            this.loadLatestCoreRelease().then( _ => {
                                                    this.isUpdating     =   false;
                                                    nsSnackBar.success( result.message ).subscribe();

                                                    setTimeout( () => {
                                                        document.location   =   currentURL;
                                                    }, 2000 );
                                                })
                                                .catch( exception => {
                                                    this.isUpdating     =   false;
                                                    nsSnackBar.error( __( 'An error has occured while fetching the update status.' ) ).subscribe()
                                                })
                                        },
                                        error : error => {
                                            this.isUpdating     =   false;
                                            nsSnackBar.error( error.message ).subscribe();
                                        }
                                    });
                            }
                        }
                    })    
                },
                loadLatestCoreRelease() {
                    this.loadingLatestCoreUpdate    =   true;
                    return new Promise( ( resolve, reject ) => {
                        nsHttpClient.get( '/api/mns/core/latest-release' )
                            .subscribe({
                                next: result => {
                                    this.loadingLatestCoreUpdate    =   false;
                                    this.currentUpdate  =   result;
                                    resolve( result );
                                },
                                error : error => {
                                    this.hasError   =   true;
                                    reject( error );
                                }
                            })
                    })
                },
                confirmDeactivate() {
                    Popup.show( nsConfirmPopup, {
                        title: __m( 'Confirm Your Action', 'MyNexoPOS' ),
                        message: __( 'The license will be detached from this domain. Do you confirm your action ?', 'MyNexoPOS' ),
                        onAction: ( action ) => {
                            if ( action ) {
                                document.location   =   deactivateURL;
                            }
                        }
                    });
                }
            }
        })
    </script>
@endsection