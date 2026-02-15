@extends('layout.dashboard')

@section('layout.dashboard.body')
<div>
    @include('RenCommissions::dashboard.partials.header')

    <div class="rc-dashboard-widgets mb-4">
        <div class="rc-widget rc-widget-wide">
            <div class="ns-box border border-box-edge h-full" style="min-height: 24rem;">
                <div class="ns-box-header p-4 border-b border-box-edge flex justify-between items-center">
                    <h3 class="font-semibold text-primary">{{ __m('Recent Commissions', 'RenCommissions') }}</h3>
                    <a class="text-info-secondary text-sm" href="{{ ns()->route('rencommissions.commissions') }}">{{ __m('View All', 'RenCommissions') }}</a>
                </div>
                <div class="ns-box-body">
                    @forelse($recent as $row)
                        <div class="p-3 border-b border-box-edge last:border-b-0">
                            <div class="flex items-center justify-between gap-2">
                                <p class="font-semibold text-primary">{{ $row->product?->name ?: __m('Unknown Product', 'RenCommissions') }}</p>
                                <p class="font-bold text-primary">{{ ns()->currency->define($row->total_commission)->format() }}</p>
                            </div>
                            <p class="text-xs text-secondary">{{ $row->order?->code ?? '#' }} | {{ $row->created_at?->diffForHumans() }}</p>
                            <p class="text-sm text-info-secondary">{{ $row->earner?->username ?? '-' }}</p>
                        </div>
                    @empty
                        <div class="p-4 text-center text-secondary">{{ __m('No commissions found for this period.', 'RenCommissions') }}</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="rc-widget">
            <div class="ns-box border border-box-edge h-full" style="min-height: 24rem;">
                <div class="ns-box-header p-4 border-b border-box-edge flex justify-between items-center">
                    <h3 class="font-semibold text-primary">{{ __m('Top Earners', 'RenCommissions') }}</h3>
                    <span class="text-xs text-info-secondary">{{ $periodOptions[$period] ?? $period }}</span>
                </div>
                <div class="ns-box-body">
                    @forelse($topEarners as $index => $row)
                        <div class="p-3 border-b border-box-edge last:border-b-0 flex items-center justify-between gap-2">
                            <div>
                                <p class="font-semibold text-primary">{{ $index + 1 }}. {{ $row->earner?->username ?? '-' }}</p>
                                <p class="text-xs text-secondary">{{ (int) $row->total_count }} {{ __m('commissions', 'RenCommissions') }}</p>
                            </div>
                            <p class="font-bold text-success">{{ ns()->currency->define($row->total_amount)->format() }}</p>
                        </div>
                    @empty
                        <div class="p-4 text-center text-secondary">{{ __m('No leaderboard data.', 'RenCommissions') }}</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="rc-widget">
            <div class="ns-box border border-box-edge h-full" style="min-height: 24rem;">
                <div class="ns-box-header p-4 border-b border-box-edge flex justify-between items-center">
                    <h3 class="font-semibold text-primary">{{ __m('Pending Payouts', 'RenCommissions') }}</h3>
                    <a class="text-info-secondary text-sm" href="{{ ns()->route('rencommissions.pending') }}">{{ __m('View All', 'RenCommissions') }}</a>
                </div>
                <div class="ns-box-body">
                    @forelse($pendingPayouts as $row)
                        <div class="p-3 border-b border-box-edge last:border-b-0">
                            <div class="flex items-center justify-between gap-2">
                                <p class="font-semibold text-primary">{{ $row->earner?->username ?? '-' }}</p>
                                <p class="font-bold text-warning">{{ ns()->currency->define($row->total_commission)->format() }}</p>
                            </div>
                            <p class="text-xs text-secondary">{{ $row->product?->name ?? '-' }}</p>
                        </div>
                    @empty
                        <div class="p-4 text-center text-secondary">{{ __m('No pending payouts.', 'RenCommissions') }}</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="mb-4 ns-box border border-box-edge">
        <div class="ns-box-header p-4 border-b border-box-edge flex justify-between items-center">
            <h3 class="font-semibold text-primary">{{ __m('Payment History', 'RenCommissions') }}</h3>
            <a class="text-info-secondary text-sm" href="{{ ns()->route('rencommissions.history') }}">{{ __m('View All', 'RenCommissions') }}</a>
        </div>
        <div class="ns-box-body overflow-auto">
            <table class="table ns-table w-full text-sm">
                <thead>
                    <tr class="info text-left">
                        <th class="p-3">{{ __m('Reference', 'RenCommissions') }}</th>
                        <th class="p-3">{{ __m('Period', 'RenCommissions') }}</th>
                        <th class="p-3">{{ __m('Entries', 'RenCommissions') }}</th>
                        <th class="p-3">{{ __m('Amount', 'RenCommissions') }}</th>
                        <th class="p-3">{{ __m('Status', 'RenCommissions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($paymentHistory as $row)
                        <tr class="border-t border-box-edge">
                            <td class="p-3 font-semibold">{{ $row->reference }}</td>
                            <td class="p-3">{{ optional($row->period_start)->toDateString() }} - {{ optional($row->period_end)->toDateString() }}</td>
                            <td class="p-3">{{ $row->entries_count }}</td>
                            <td class="p-3 font-semibold">{{ ns()->currency->define($row->total_amount)->format() }}</td>
                            <td class="p-3 capitalize">{{ $row->status }}</td>
                        </tr>
                    @empty
                        <tr><td class="p-4 text-secondary" colspan="5">{{ __m('No payment history.', 'RenCommissions') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="ns-box border border-box-edge">
        <div class="ns-box-header p-4 border-b border-box-edge flex justify-between items-center">
            <h3 class="font-semibold text-primary">{{ __m('Commission Trends', 'RenCommissions') }}</h3>
            <span class="text-xs text-info-secondary">{{ $periodOptions[$period] ?? $period }}</span>
        </div>
        <div class="ns-box-body overflow-auto">
            <table class="table ns-table w-full text-sm">
                <thead>
                    <tr class="info text-left">
                        <th class="p-3">{{ __m('Date', 'RenCommissions') }}</th>
                        <th class="p-3">{{ __m('Count', 'RenCommissions') }}</th>
                        <th class="p-3">{{ __m('Amount', 'RenCommissions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($trend as $row)
                        <tr class="border-t border-box-edge">
                            <td class="p-3">{{ $row->date }}</td>
                            <td class="p-3">{{ $row->count }}</td>
                            <td class="p-3 font-semibold">{{ ns()->currency->define($row->total)->format() }}</td>
                        </tr>
                    @empty
                        <tr><td class="p-4 text-secondary" colspan="3">{{ __m('No trend data.', 'RenCommissions') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
@endsection

@section('layout.dashboard.footer')
    @parent
    <style>
        #dashboard-content .rc-dashboard-widgets {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        #dashboard-content .rc-widget {
            min-width: 0;
        }

        @media (min-width: 1200px) {
            #dashboard-content .rc-dashboard-widgets {
                grid-template-columns: 2fr 1fr 1fr;
            }

            #dashboard-content .rc-widget-wide {
                grid-column: span 1;
            }
        }
    </style>
@endsection
