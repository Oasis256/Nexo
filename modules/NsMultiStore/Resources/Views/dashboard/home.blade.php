@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body.with-header' )
    <div id="cards">
        <ns-multistore-dashboard></ns-multistore-dashboard>
    </div>
@endsection

@section( 'layout.dashboard.footer.inject' )
    @parent
    <script type="module">
        nsExtraComponents.nsMultistoreDashboard   =   defineComponent({
            template: `
            <div>
                <div class="flex -mx-4 mb-4">
                    <div class="px-4 w-1/3">
                        <div class="shadow rounded-lg p-3 bg-gradient-to-br from-blue-400 to-blue-600">
                            <h3 class="text-white font-semibold">{{ __m( 'Complete Sales', 'NsMultiStore' ) }}</h3>
                            <h2 class="text-white font-bold text-4xl">@{{ getReportData( 'total_paid_orders' ) }}</h2>
                            <div class="w-full flex justify-end">
                                <span class="text-sm text-gray-200">{{ __m( 'Today', 'NsMultiStore' ) }} : @{{ nsCurrency( getReportData( 'day_paid_orders' ) ) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="px-4 w-1/3">
                        <div class="shadow rounded-lg p-3 bg-gradient-to-br from-green-400 to-green-600">
                            <h3 class="text-white font-semibold">{{ __m( 'Income', 'NsMultiStore' ) }}</h3>
                            <h2 class="text-white font-bold text-4xl">@{{ getReportData( 'total_income' ) }}</h2>
                            <div class="w-full flex justify-end">
                                <span class="text-sm text-gray-200">{{ __m( 'Today', 'NsMultiStore' ) }} : @{{ nsCurrency( getReportData( 'day_income' ) ) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="px-4 w-1/3">
                        <div class="shadow rounded-lg p-3 bg-gradient-to-br from-red-400 to-red-600">
                            <h3 class="text-white font-semibold">{{ __m( 'Waste', 'NsMultiStore' ) }}</h3>
                            <h2 class="text-white font-bold text-4xl">@{{ getReportData( 'total_wasted_goods' ) }}</h2>
                            <div class="w-full flex justify-end">
                                <span class="text-sm text-gray-200">{{ __m( 'Today', 'NsMultiStore' ) }} : @{{ nsCurrency( getReportData( 'day_wasted_goods' ) ) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="-mx-4 mb-4 flex flex-wrap">
                    <div class="px-4 w-1/2">
                        <div class="shadow rounded ns-box">
                            <div class="p-2 border-b ns-box-header">
                                <h3 class="font-semibold">{{ __m( 'Stores Income', 'NsMultiStore' ) }}</h3>
                            </div>
                            <div v-if="! hasLoaded" class="h-56 flex items-center justify-center">
                                <ns-spinner></ns-spinner>
                            </div>
                            <div v-if="storeReport.length === 0 && hasLoaded" class=" h-56 flex items-center justify-center flex-col">
                                <span class="rounded-full multi:h-28 multi:w-28 border border-box-edge mb-4 flex items-center justify-center">
                                    <i class="las la-store text-6xl"></i>
                                </span>
                                <h3 class="font-semibold multi:text-2xl">{{ __m( 'Looks like there is no store', 'NsMultiStore' ) }}</h3>
                            </div>
                            <div
                                v-for="store of storeReport"
                                :key="store.id" 
                                class="border-b ns-box-body p-2 flex justify-between">
                                <div class="flex-auto">
                                    <div class="flex -mx-2 justify-between">
                                        <div class="px-2">
                                            <h3 class="text-lg font-semibold">@{{ store.name }}</h3>
                                        </div>
                                        <div class="px-2">
                                            <h3 class="text-lg font-semibold">@{{ nsCurrency( store.today_report ? store.today_report.day_income : 0 ) }}</h3>
                                        </div>
                                    </div>
                                    <div class="flex flex-col -mx-2">
                                        <div class="px-2 py-1">
                                            <h4 class="text-semibold text-xs text-secondary">
                                                <span>{{ __m( 'Complete Sales :', 'NsMultiStore' ) }}</span>
                                                <span>@{{ nsCurrency( store.today_report ? store.today_report.total_paid_orders : 0 ) }}</span>
                                            </h4>
                                        </div>
                                        <div class="px-2 py-1">
                                            <h4 class="text-semibold text-xs text-secondary">
                                                <span>{{ __m( 'Total Transactions :', 'NsMultiStore' ) }}</span>
                                                <span>@{{ nsCurrency( store.today_report ? store.today_report.total_transactions : 0 ) }}</span>
                                            </h4>
                                        </div>
                                        <div class="px-2 py-1">
                                            <h4 class="text-semibold text-xs text-secondary">
                                                <span>{{ __m( 'Total Taxes :', 'NsMultiStore' ) }}</span>
                                                <span>@{{ nsCurrency( store.today_report ? store.today_report.total_taxes : 0 ) }}</span>
                                            </h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="px-4 w-1/2">
                        <div class="shadow rounded ns-box">
                            <div class="p-2 border-b ns-box-body">
                                <h3 class="font-semibold text-primary">{{ __m( 'Other Details', 'NsMultiStore' ) }}</h3>
                            </div>
                            <div
                                class="border-b ns-box-body flex justify-between">
                                <div class="flex-auto">
                                    <div class="flex flex-col -mx-2 justify-between">
                                        <div class="multi:px-2">
                                            <div class="border-b border-box-edge w-full flex">
                                                <div class="multi:p-2 flex-auto">
                                                    <h3 class="text-lg font-semibold text-primary">{{ __m( 'Total Transactions', 'NsMultiStore' ) }}</h3>
                                                    <h4 class="text-sm text-secondary">{{ __m( 'Yesterday', 'NsMultiStore' ) }}</h4>
                                                </div>
                                                <div class="multi:p-2 flex-auto flex flex-col items-end">
                                                    <h3 class="text-lg font-semibold text-primary">@{{ nsCurrency( getReportData( 'total_transactions' ) ) }}</h3>
                                                    <h4 class="text-sm text-secondary">@{{ nsCurrency( getReportData( 'total_transactions', 'yesterday_report' ) ) }}</h4>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="multi:px-2">
                                            <div class="border-b border-box-edge w-full flex">
                                                <div class="multi:p-2 flex-auto">
                                                    <h3 class="text-lg font-semibold text-primary">{{ __m( 'Total Discounts', 'NsMultiStore' ) }}</h3>
                                                    <h4 class="text-sm text-secondary">{{ __m( 'Yesterday', 'NsMultiStore' ) }}</h4>
                                                </div>
                                                <div class="multi:p-2 flex-auto flex flex-col items-end">
                                                    <h3 class="text-lg font-semibold text-primary">@{{ nsCurrency( getReportData( 'total_discounts' ) ) }}</h3>
                                                    <h4 class="text-sm text-secondary">@{{ nsCurrency( getReportData( 'total_discounts', 'yesterday_report' ) ) }}</h4>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="multi:px-2">
                                            <div class="border-b border-box-edge flex w-full">    
                                                <div class="multi:p-2 flex-auto">
                                                    <h3 class="text-lg font-semibold text-primary">{{ __m( 'Partially Paid Orders', 'NsMultiStore' ) }} (x@{{ getReportData( 'total_partially_paid_orders_count' ) }})</h3>
                                                    <h4 class="text-sm text-secondary">{{ __m( 'Yesterday', 'NsMultiStore' ) }} (x@{{ getReportData( 'total_partially_paid_orders_count', 'yesterday_report' ) }})</h4>
                                                </div>
                                                <div class="multi:p-2 flex-auto flex flex-col items-end">
                                                    <h3 class="text-lg font-semibold text-primary">@{{ nsCurrency( getReportData( 'total_partially_paid_orders' ) ) }}</h3>
                                                    <h4 class="text-sm text-secondary">@{{ nsCurrency( getReportData( 'total_partially_paid_orders', 'yesterday_report' ) ) }}</h4>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="multi:px-2">
                                            <div class="border-b border-box-edge flex w-full">    
                                                <div class="multi:p-2 flex-auto">
                                                    <h3 class="text-lg font-semibold text-primary">{{ __m( 'Total Taxes', 'NsMultiStore' ) }}</h3>
                                                    <h4 class="text-sm text-secondary">{{ __m( 'Yesterday', 'NsMultiStore' ) }}</h4>
                                                </div>
                                                <div class="multi:p-2 flex-auto flex flex-col items-end">
                                                    <h3 class="text-lg font-semibold text-primary">@{{ nsCurrency( getReportData( 'total_taxes' ) ) }}</h3>
                                                    <h4 class="text-sm text-secondary">@{{ nsCurrency( getReportData( 'total_taxes', 'yesterday_report' ) ) }}</h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            `,
            name: 'ns-multistore-dashboard',
            data() {
                return {
                    storeReport : [],
                    hasLoaded: false,
                    nsCurrency,
                }
            },
            mounted() {
                this.loadStores();
            },
            computed: {
                // ...
            },
            methods: {
                getReportData( value, type = 'today_report' ) {
                    if ( this.storeReport.length > 0 ) {
                        const total =     this.storeReport.map( store => {
                            return store[ type ] ? store[ type ][ value ] : 0;
                        });

                        if ( total.length > 0 ) {
                            return total.reduce( ( b, a ) => b + a );
                        }
                    }
                    
                    return 0;
                },
                loadStores() {
                    this.hasLoaded  =   false;
                    nsHttpClient.get( '/api/multistores/stores-details' )
                        .subscribe( result => {
                            this.hasLoaded      =   true;
                            this.storeReport    =   result;
                        }, ( error ) => {
                            this.hasLoaded      =   true;
                            nsSnackBar.error( error.message ).subscribe();
                        });
                }
            }
        });
    </script>
@endsection