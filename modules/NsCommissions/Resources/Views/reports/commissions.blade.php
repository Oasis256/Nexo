@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
<div class="h-full overflow-hidden flex flex-col">
    @include( Hook::filter( 'ns-dashboard-header', '../common/dashboard-header' ) )
    <div id="dashboard-content" class="overflow-hidden flex flex-auto w-full">
        <commission-report inline-template>
        <div id="report-section" class="px-4 w-full">
                <div class="flex -mx-2">
                    <div class="px-2">
                        <component v-bind:is="dateTime" :date="startDate" @change="setStartDate( $event )">
                    </div>
                    <div class="px-2">
                        <component v-bind:is="dateTime" :date="endDate" @change="setEndDate( $event )">
                    </div>
                    <!-- <div class="px-2">
                        <ns-datetimepicker :field="field" @change="setEndDate( $event )"></ns-datetimepicker>
                    </div> -->
                    <div class="px-2">
                        <div class="ns-button">
                            <button @click="loadReport()" class="rounded flex justify-between shadow py-1 items-center px-2">
                                <i class="las la-sync-alt text-xl"></i>
                                <span class="pl-2">{{ __m( 'Load', 'NsCommissions' ) }}</span>
                            </button>
                        </div>
                    </div>
                    <div class="px-2">
                        <div class="ns-button">
                            <button @click="printSaleReport()" class="rounded flex justify-between shadow py-1 items-center px-2">
                                <i class="las la-print text-xl"></i>
                                <span class="pl-2">{{ __m( 'Print', 'NsCommissions' ) }}</span>
                            </button>
                        </div>
                    </div>
                </div>
                <div id="report" class="anim-duration-500 fade-in-entrance">
                    <div class="flex w-full">
                        <div class="my-4 flex justify-between w-full">
                            <div class="text-primary">
                                <ul>
                                    <li class="pb-1 border-b border-dashed">{{ sprintf( __( 'Date : %s' ), ns()->date->getNowFormatted() ) }}</li>
                                    <li class="pb-1 border-b border-dashed">{{ __( 'Document : Commissions' ) }}</li>
                                    <li class="pb-1 border-b border-dashed">{{ sprintf( __( 'By : %s' ), Auth::user()->username ) }}</li>
                                </ul>
                            </div>
                            <div>
                                <img class="w-72" src="{{ ns()->option->get( 'ns_store_rectangle_logo' ) }}" alt="{{ ns()->option->get( 'ns_store_name' ) }}">
                            </div>
                        </div>
                    </div>
                    <div class="ns-box shadow rounded my-4">
                        <div class="border-b">
                            <table class="table w-full ns-table">
                                <thead class="">
                                    <tr>
                                        <th width="200" class="border p-2 text-left">{{ __m( 'Worker', 'NsCommissions' ) }}</th>
                                        <th width="150" class="text-right border p-2">{{ __m( 'Total Orders', 'NsCommissions' ) }}</th>
                                        <th width="150" class="text-right border p-2">{{ __m( 'Sales', 'NsCommissions' ) }}</th>
                                        <th width="150" class="text-right border p-2">{{ __m( 'Commissions', 'NsCommissions' ) }}</th>
                                    </tr>
                                </thead>
                                <tbody class="">
                                    <tr v-for="commission of commissions" :key="commission.id">
                                        <td class="p-2 border">@{{ commission.username }}</td>
                                        <td class="p-2 border text-right">@{{ commission.total_sales_count }}</td>
                                        <td class="p-2 border text-right">@{{ commission.total_sales | currency }}</td>
                                        <td class="p-2 border text-right">@{{ commission.commissions | currency }}</td>
                                    </tr>
                                </tbody>
                                <tfoot class="font-semibold">
                                    <tr>
                                        <td class="p-2 border"></td>
                                        <td class="p-2 border text-right">@{{ totalCount | currency }}</td>
                                        <td class="p-2 border text-right">@{{ totalSales | currency }}</td>
                                        <td class="p-2 border text-right">@{{ totalCommissions | currency }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </commission-report>
    </div>
</div>
@endsection

@section( 'layout.dashboard.footer.inject' )
    @parent
    <script>
        Vue.component( 'commission-report', {
            mounted() {
                console.log( this );
            },
            data() {
                return {
                    dateTime: nsComponents.nsDateTimePicker,
                    startDate: moment(),
                    endDate: moment(),
                    commissions: [],
                    field: {
                        type: 'datetimepicker',
                        value: '2021-02-07',
                        name: 'date'
                    }
                }
            },
            computed: {
                totalCount() {
                    if ( this.commissions.length > 0 ) {
                        return this.commissions
                            .map( commission => parseFloat( commission.total_sales_count ) )
                            .reduce( ( before, after ) => before + after );
                    }
                    return 0;
                },
                totalSales() {
                    if ( this.commissions.length > 0 ) {
                        return this.commissions
                            .map( commission => parseFloat( commission.total_sales ) )
                            .reduce( ( before, after ) => before + after );
                    }
                    return 0;
                },
                totalCommissions() {
                    if ( this.commissions.length > 0 ) {
                        return this.commissions
                            .map( commission => parseFloat( commission.commissions ) )
                            .reduce( ( before, after ) => before + after );
                    }

                    return 0;
                }
            },
            methods: {
                setStartDate( moment ) {
                    this.startDate  =   moment.format();
                },
                setEndDate( moment ) {
                    this.endDate    =   moment.format();
                },
                printSaleReport() {
                    this.$htmlToPaper( 'report' );
                },
                loadReport() {
                    nsHttpClient.post( `/api/nexopos/v4/commissions/reports`, {
                        startDate: this.startDate,
                        endDate: this.endDate
                    }).subscribe( result => {
                        this.commissions     =   result;
                    })
                }
            }
        })
    </script>
@endsection