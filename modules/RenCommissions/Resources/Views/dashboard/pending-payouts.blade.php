@extends('layout.dashboard')

@section('layout.dashboard.body')
<div>
    @include('RenCommissions::dashboard.partials.header')

    <form method="POST" action="{{ ns()->route('rencommissions.pending.create-payout') }}" class="ns-box border">
        @csrf
        <div class="p-4 border-b flex items-center justify-between gap-4">
            <p class="text-sm text-secondary">{{ __m('Select pending commissions then create one payout batch.', 'RenCommissions') }}</p>
            <div class="flex gap-2">
                <input type="text" name="notes" class="ns-input text-sm" placeholder="{{ __m('Optional notes', 'RenCommissions') }}">
                <button class="ns-button info text-sm">{{ __m('Create Payout', 'RenCommissions') }}</button>
            </div>
        </div>

        <div class="overflow-auto">
            <table class="table ns-table w-full text-sm">
                <thead>
                    <tr class="info text-left">
                        <th class="p-3"><input type="checkbox" id="select-all-pending"></th>
                        <th class="p-3">{{ __m('Date', 'RenCommissions') }}</th>
                        <th class="p-3">{{ __m('Order', 'RenCommissions') }}</th>
                        <th class="p-3">{{ __m('Product', 'RenCommissions') }}</th>
                        <th class="p-3">{{ __m('Earner', 'RenCommissions') }}</th>
                        <th class="p-3">{{ __m('Amount', 'RenCommissions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr class="border-t">
                            <td class="p-3"><input type="checkbox" class="pending-row" name="commission_ids[]" value="{{ $row->id }}"></td>
                            <td class="p-3">{{ $row->created_at?->toDateString() }}</td>
                            <td class="p-3">{{ $row->order?->code ?? '-' }}</td>
                            <td class="p-3">{{ $row->product?->name ?? '-' }}</td>
                            <td class="p-3">{{ $row->earner?->username ?? '-' }}</td>
                            <td class="p-3 font-semibold text-orange-600">{{ ns()->currency->define($row->total_commission)->format() }}</td>
                        </tr>
                    @empty
                        <tr><td class="p-4 text-secondary" colspan="6">{{ __m('No pending payouts.', 'RenCommissions') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t">{{ $rows->links() }}</div>
    </form>
</div>
</div>
@endsection

@section('layout.dashboard.footer')
    @parent
    <script>
        const master = document.getElementById('select-all-pending');
        if (master) {
            master.addEventListener('change', () => {
                document.querySelectorAll('.pending-row').forEach((entry) => {
                    entry.checked = master.checked;
                });
            });
        }
    </script>
@endsection
