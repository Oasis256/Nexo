@extends('layout.dashboard')

@section('layout.dashboard.body')
<div>
    @include('RenCommissions::dashboard.partials.header')

    <div class="ns-box border overflow-auto">
        <table class="table ns-table w-full text-sm">
            <thead>
                <tr class="info text-left">
                    <th class="p-3">{{ __m('Date', 'RenCommissions') }}</th>
                    <th class="p-3">{{ __m('Order', 'RenCommissions') }}</th>
                    <th class="p-3">{{ __m('Product', 'RenCommissions') }}</th>
                    <th class="p-3">{{ __m('Type', 'RenCommissions') }}</th>
                    <th class="p-3">{{ __m('Amount', 'RenCommissions') }}</th>
                    <th class="p-3">{{ __m('Status', 'RenCommissions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr class="border-t">
                        <td class="p-3">{{ $row->created_at?->toDateString() }}</td>
                        <td class="p-3">{{ $row->order?->code ?? '-' }}</td>
                        <td class="p-3">{{ $row->product?->name ?? '-' }}</td>
                        <td class="p-3">{{ $row->type?->name ?? ucfirst($row->commission_method) }}</td>
                        <td class="p-3 font-semibold">{{ ns()->currency->define($row->total_commission)->format() }}</td>
                        <td class="p-3 capitalize">{{ $row->status }}</td>
                    </tr>
                @empty
                    <tr><td class="p-4 text-secondary" colspan="6">{{ __m('No commissions found.', 'RenCommissions') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4 border-t">{{ $rows->links() }}</div>
    </div>
</div>
</div>
@endsection
