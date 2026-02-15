@extends( 'layout.base' )

@section( 'layout.base.body' )
    <div id="vendor-app" class="h-full w-full bg-gray-300 flex">
        <div class="container mx-auto flex-auto items-center justify-center flex">
            <div id="sign-in-box" class="w-full md:w-2/3 lg:w-1/3">
                <div class="flex justify-center items-center py-6">
                    <img class="w-32" src="/svg/nexopos-variant-1.svg" alt="NexoPOS">
                </div>
                <div class="my-3 rounded shadow bg-white">
                    <div class="border-b border-gray-200 py-4 flex items-center justify-center">
                        <h3 class="text-xl font-bold text-gray-700">Vendor Installation</h3>
                    </div>
                    <div class="p-2">
                        <p class="text-center text-sm text-gray-600 py-4">{{ __m( 'Some Modules requires Composer vendor (packages) to be installed in order to work properly. This will proceed to the vendor installation, the process might take some time and can still be executed manually if it fails', 'MyNexoPOS' ) }}</p>
                        <div v-if="error" class="border-l-4 text-sm border-red-600 bg-red-200 p-4 text-gray-700">
                            <p>
                                Looks like an error has occured during the installation. Usually, giving another shot should fix that. However, if you still don't get any chance.
                            Please report this message to the support : 
                            </p>
                            <pre class="rounded whitespace-pre-wrap bg-gray-700 text-white my-2 p-2">@{{ lastErrorMessage }}</pre>
                        </div>
                    </div>
                    <div class="border-t border-gray-200 p-2 flex justify-between">
                        <div>
                            <button v-if="error" @click="proceedUpdate()" class="rounded bg-red-400 shadow-inner text-white p-2">
                                <i class="las la-sync"></i>
                                <span>{{ __m( 'Try Again', 'MyNexoPOS' ) }}</span>
                            </button>
                        </div>
                        <div class="flex">
                            <button v-if="installing" class="rounded bg-blue-400 shadow-inner text-white p-2">
                                <i class="las la-sync animate-spin"></i>
                                <span v-if="! vendorInstalled">{{ __m( 'Installing', 'MyNexoPOS' )}}...</span>
                                <span class="mr-1" v-if="! vendorInstalled">@{{ index }}/@{{ modules.length }}</span>
                            </button>
                            <a :href="returnLink" v-if="! installing" class="rounded bg-blue-400 shadow-inner text-white p-2">
                                <i class="las la-undo"></i>
                                <span>{{ __m( 'Return', 'MyNexoPOS' ) }}</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section( 'layout.base.footer' )
    @parent
    <script>
        const InstallationData    =   {
            returnLink: '{{ $redirect ?? ( ! in_array( url()->previous(), [ ns()->route( "ns.database-update" ), ns()->route( "ns.do-setup" ) ]) ? url()->previous() : ns()->route( "ns.dashboard.home" ) ) }}',
            modules: @json( $modules ),
            installURL: `{{ route( 'mynexopos.vendor-installation' ) }}`
        }
    </script>
    <script>
        new Vue({
            el: '#vendor-app',
            data: {
                ...InstallationData,
                error: null,
                lastErrorMessage: null,
                vendorInstalled: false,
                installing: true,
                index: 0,
                files: []
            },
            mounted() {
                this.launchInstallation();
            },
            methods: {
                async launchInstallation() {
                    this.installing     =   true;
                    for( let i = this.index; i < this.modules.length; i++ ) {
                        try {
                            await this.updateModule( this.modules[i] );
                            this.index++;
                        } catch( exception ) {
                            this.error              =   true;
                            this.lastErrorMessage   =   exception.message || __m( 'An error has occured while installing.', 'MyNexoPOS' );
                            this.installing         =   false;
                        }
                    }

                    document.location   =   this.returnLink;
                },
                updateModule( module ) {
                    return new Promise( ( resolve, reject ) => {
                        nsHttpClient.post( this.installURL, { module })
                            .subscribe({
                                next: result => {
                                    resolve( result );
                                },
                                error: error => {
                                    reject( error );
                                }
                            })
                    })
                }
            }
        })
    </script>
@endsection