@extends( 'layout.base' )

@section( 'layout.base.body' )
    <div id="update-store" class="h-full w-full bg-surface flex">
        <div class="container mx-auto flex-auto items-center justify-center flex">
            <div id="sign-in-box" class="w-full md:w-2/3 lg:w-2/5">
                <div class="flex justify-center items-center py-6">
                    <h2 class="text-6xl font-bold text-transparent bg-clip-text from-blue-500 to-indigo-500 bg-gradient-to-br">{{ ns()->option->get( 'store_name' ) ?: __m( 'NexoPOS', 'NsMultiStore' ) }}</h2>
                </div>
                <div class="my-3 rounded shadow bg-box-background">
                    <div class="border-b border-box-edge py-4 flex items-center justify-center">
                        <h3 class="text-xl font-bold text-fontcolor">{{ __m( 'Datebase Update', 'NsMultiStore' ) }}</h3>
                    </div>
                    <div class="p-2">
                        <p class="text-center text-sm text-fontcolor py-4">{{ sprintf( __m( 'A database migration is requested for "%s". Migration helps to keep the database in sync with the files changes. This process shouldn\'t take that much time.', 'NsMultiStore' ), $store->name ) }}</p>
                        <div v-if="error" class="border-l-4 text-sm border-error-secondary bg-error-primary p-4 text-fontcolor">
                            <p class="text-white">
                            {{ __m( 'Looks like an error has occured during the update. Usually, giving another shot should fix that. However, if you still don\'t get any chance.
                                Please report this message to the support : ', 'NsMultiStore' ) }}
                            </p>
                            <pre class="rounded whitespace-pre-wrap bg-gray-700 text-white my-2 p-2">AZEAZE</pre>
                        </div>
                    </div>
                    <div class="border-t border-box-edge p-2 flex justify-between">
                        <div>
                            <button v-if="error" @click="proceedUpdate()" class="rounded bg-error-secondary shadow-inner text-white p-2">
                                <i class="las la-sync"></i>
                                <span>{{ __m( 'Try Again', 'NsMultiStore' ) }}</span>
                            </button>
                        </div>
                        <div class="flex">
                            <button v-if="updating" class="rounded bg-input-button shadow-inner border-input-edge shadow border text-white p-2">
                                <i class="las la-sync animate-spin"></i>
                                <span>{{ __m( 'Migrating...', 'NsMultiStore' ) }} @{{ currentIndex + '/' + totalIndex }}</span>
                            </button>
                            <a :href="returnLink" v-if="! updating" class="rounded bg-input-button border-input-edge shadow border shadow-inner text-white p-2">
                                <i class="las la-undo"></i>
                                <span>{{ __m( 'Migrating...', 'NsMultiStore' ) }}</span>
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
    const UpdateData        =   <?php echo json_encode([
        'migrations'        =>  $migrations,
        'returnLink'        =>  $callback,
        'store'             =>  $store,
        'migrateStoreUrl'   =>  route( 'ns.multistore.run-migration', [
            $store->id
        ])
    ]);?>
    </script>

    <script>
        document.addEventListener( 'DOMContentLoaded', () => {
            const UpdateApp     =   createApp({
                data() {
                    return {
                        updating: false,
                        currentIndex: 0,
                        totalIndex: 0,
                        error: false,
                        errorMessage : '',
                        ...UpdateData
                    }
                },
                mounted() {
                    this.proceedUpdate();
                },
                methods: {
                    async proceedUpdate() {
                        this.updating   =   true;
                        let loopIndex       =   0;
                        this.totalIndex     =   Object.values( this.migrations ).length;

                        for( let index in this.migrations ) {
                            try {
                                loopIndex++;
                                this.currentIndex   =   loopIndex;

                                await new Promise( ( resolve, reject ) => {
                                    setTimeout( () => {
                                        nsHttpClient.post( this.migrateStoreUrl, {
                                            file    : this.migrations[ index ]
                                        }).subscribe( result => {
                                            resolve( result );
                                        }, ( error ) => {
                                            this.error  =   true;
                                            this.errorMessage   =   error.message;
                                            reject( error );
                                            throw error.message;
                                        })
                                    }, 1000 );
                                });
                            } catch ( exception ) {
                                console.log( exception );
                                return;
                                break;
                            }
                        }

                        this.updating       =   false;
                        document.location   =   this.returnLink;
                    }
                }
            });

            UpdateApp.mount( '#update-store' );
        });
    </script>
@endsection