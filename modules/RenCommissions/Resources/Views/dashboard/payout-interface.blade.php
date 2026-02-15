@extends('layout.dashboard')

@section('layout.dashboard.body')
<div>
    @include('RenCommissions::dashboard.partials.header')

    <form method="GET" class="ns-box border border-box-edge p-4 mb-4 grid grid-cols-1 md:grid-cols-5 gap-3">
        <div>
            <label class="text-xs text-secondary uppercase">{{ __m('Biweekly Window', 'RenCommissions') }}</label>
            <select name="biweekly" class="ns-select">
                <option value="first_half" @selected($biweekly === 'first_half')>{{ __m('1st - 14th', 'RenCommissions') }}</option>
                <option value="second_half" @selected($biweekly === 'second_half')>{{ __m('15th - End', 'RenCommissions') }}</option>
                <option value="all" @selected($biweekly === 'all')>{{ __m('Full Month', 'RenCommissions') }}</option>
            </select>
        </div>
        <div>
            <label class="text-xs text-secondary uppercase">{{ __m('Daily Date', 'RenCommissions') }}</label>
            <input type="date" name="daily_date" value="{{ $dailyDate }}" class="ns-input">
        </div>
        <div>
            <label class="text-xs text-secondary uppercase">{{ __m('Scope', 'RenCommissions') }}</label>
            <select name="scope" class="ns-select">
                <option value="global" @selected($scope === 'global')>{{ __m('Global', 'RenCommissions') }}</option>
                <option value="earner" @selected($scope === 'earner')>{{ __m('Per Earner', 'RenCommissions') }}</option>
            </select>
        </div>
        <div>
            <label class="text-xs text-secondary uppercase">{{ __m('Earner (Optional)', 'RenCommissions') }}</label>
            <select name="earner_id" class="ns-select">
                <option value="0">{{ __m('All Earners', 'RenCommissions') }}</option>
                @foreach($earners as $earner)
                    <option value="{{ $earner->id }}" @selected((int) $earnerId === (int) $earner->id)>{{ $earner->username }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end">
            <button class="ns-button info w-full">{{ __m('Apply Filters', 'RenCommissions') }}</button>
        </div>
    </form>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
        <div class="ns-box border border-box-edge p-4">
            <p class="text-xs text-secondary uppercase">{{ __m('Pending Entries', 'RenCommissions') }}</p>
            <p class="text-3xl font-bold text-primary">{{ $pendingRows->total() }}</p>
        </div>
        <div class="ns-box border border-box-edge p-4">
            <p class="text-xs text-secondary uppercase">{{ __m('Pending Amount', 'RenCommissions') }}</p>
            <p class="text-3xl font-bold text-warning">{{ ns()->currency->define($pendingAmount)->format() }}</p>
        </div>
        <div class="ns-box border border-box-edge p-4">
            <p class="text-xs text-secondary uppercase">{{ __m('Quick Action', 'RenCommissions') }}</p>
            <div class="mt-2 flex gap-2">
                <form method="POST" action="{{ ns()->route('rencommissions.pending.create-payout') }}">
                    @csrf
                    <input type="hidden" name="notes" value="{{ __m('Post all pending from payout interface.', 'RenCommissions') }}">
                    <button class="ns-button info">{{ __m('Post All Pending', 'RenCommissions') }}</button>
                </form>
                <form method="POST" action="{{ ns()->route('rencommissions.pending.create-payout-by-earner') }}">
                    @csrf
                    <input type="hidden" name="notes" value="{{ __m('Post all pending by earner from payout interface.', 'RenCommissions') }}">
                    <button class="ns-button success">{{ __m('Post All By Earner', 'RenCommissions') }}</button>
                </form>
            </div>
        </div>
    </div>

    <div class="ns-box border border-box-edge p-4 mb-4">
        <div class="flex items-center justify-between">
            <h3 class="font-semibold text-primary">{{ __m('Daily Snapshot', 'RenCommissions') }}</h3>
            <span class="text-xs text-secondary">{{ $dailyDate }}</span>
        </div>
        <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-3">
            <div class="border border-box-edge rounded p-3">
                <p class="text-xs text-secondary uppercase">{{ __m('Global Daily Count', 'RenCommissions') }}</p>
                <p class="text-2xl font-bold text-primary">{{ $dailyGlobal['count'] }}</p>
            </div>
            <div class="border border-box-edge rounded p-3">
                <p class="text-xs text-secondary uppercase">{{ __m('Global Daily Amount', 'RenCommissions') }}</p>
                <p class="text-2xl font-bold text-success">{{ ns()->currency->define($dailyGlobal['amount'])->format() }}</p>
            </div>
        </div>
        @if($scope === 'earner')
            <div class="mt-3 overflow-auto">
                <table class="table ns-table w-full text-sm">
                    <thead>
                        <tr class="info text-left">
                            <th class="p-3">{{ __m('Earner', 'RenCommissions') }}</th>
                            <th class="p-3">{{ __m('Daily Count', 'RenCommissions') }}</th>
                            <th class="p-3">{{ __m('Daily Amount', 'RenCommissions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dailyByEarner as $entry)
                            <tr class="border-t border-box-edge">
                                <td class="p-3">{{ $entry->earner?->username ?? '-' }}</td>
                                <td class="p-3">{{ (int) $entry->total_count }}</td>
                                <td class="p-3 font-semibold">{{ ns()->currency->define($entry->total_amount)->format() }}</td>
                            </tr>
                        @empty
                            <tr><td class="p-3 text-secondary" colspan="3">{{ __m('No earner daily data for selected filter.', 'RenCommissions') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <form method="POST" action="{{ ns()->route('rencommissions.pending.create-payout') }}" class="ns-box border border-box-edge mb-4">
        @csrf
        <div class="p-4 border-b border-box-edge flex items-center justify-between gap-3">
            <p class="text-sm text-secondary">{{ __m('Select one or more pending entries and post a payout batch.', 'RenCommissions') }}</p>
            <div class="flex items-center gap-2">
                <input
                    type="text"
                    name="notes"
                    class="ns-input text-sm"
                    placeholder="{{ __m('Optional payout note', 'RenCommissions') }}"
                >
                <button class="ns-button success">{{ __m('Post Selected', 'RenCommissions') }}</button>
                <button
                    class="ns-button info"
                    formaction="{{ ns()->route('rencommissions.pending.create-payout-by-earner') }}"
                    formmethod="POST"
                >
                    {{ __m('Post Selected By Earner', 'RenCommissions') }}
                </button>
            </div>
        </div>

        <div class="overflow-auto">
            <table class="table ns-table w-full text-sm">
                <thead>
                    <tr class="info text-left">
                        <th class="p-3"><input type="checkbox" id="rc-select-all"></th>
                        <th class="p-3">{{ __m('Date', 'RenCommissions') }}</th>
                        <th class="p-3">{{ __m('Order', 'RenCommissions') }}</th>
                        <th class="p-3">{{ __m('Product', 'RenCommissions') }}</th>
                        <th class="p-3">{{ __m('Earner', 'RenCommissions') }}</th>
                        <th class="p-3">{{ __m('Amount', 'RenCommissions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pendingRows as $row)
                        <tr class="border-t border-box-edge">
                            <td class="p-3"><input type="checkbox" class="rc-row" name="commission_ids[]" value="{{ $row->id }}"></td>
                            <td class="p-3">{{ optional($row->created_at)->toDateString() }}</td>
                            <td class="p-3">{{ $row->order?->code ?? '-' }}</td>
                            <td class="p-3">{{ $row->product?->name ?? '-' }}</td>
                            <td class="p-3">{{ $row->earner?->username ?? '-' }}</td>
                            <td class="p-3 font-semibold">{{ ns()->currency->define($row->total_commission)->format() }}</td>
                        </tr>
                    @empty
                        <tr><td class="p-4 text-secondary" colspan="6">{{ __m('No pending payouts.', 'RenCommissions') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-box-edge">
            {{ $pendingRows->links() }}
        </div>
    </form>

    <div class="ns-box border border-box-edge">
        <div class="p-4 border-b border-box-edge">
            <h3 class="font-semibold text-primary">{{ __m('Recent Payout Batches', 'RenCommissions') }}</h3>
        </div>
        <div class="overflow-auto">
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
                    @forelse($recentPayouts as $row)
                        <tr class="border-t border-box-edge">
                            <td class="p-3 font-semibold">{{ $row->reference }}</td>
                            <td class="p-3">{{ optional($row->period_start)->toDateString() }} - {{ optional($row->period_end)->toDateString() }}</td>
                            <td class="p-3">{{ $row->entries_count }}</td>
                            <td class="p-3 font-semibold">{{ ns()->currency->define($row->total_amount)->format() }}</td>
                            <td class="p-3 capitalize">{{ $row->status }}</td>
                        </tr>
                    @empty
                        <tr><td class="p-4 text-secondary" colspan="5">{{ __m('No payout batches yet.', 'RenCommissions') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('layout.dashboard.footer')
    @parent
    <script>
        const rcSelectAll = document.getElementById('rc-select-all');
        if (rcSelectAll) {
            rcSelectAll.addEventListener('change', () => {
                document.querySelectorAll('.rc-row').forEach((entry) => {
                    entry.checked = rcSelectAll.checked;
                });
            });
        }
    </script>
@endsection
