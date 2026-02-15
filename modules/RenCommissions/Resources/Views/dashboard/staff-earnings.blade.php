@extends('layout.dashboard')

@section('layout.dashboard.body')
<div>
    @include('RenCommissions::dashboard.partials.header')

    <div class="ns-box border overflow-auto">
        <table class="table ns-table w-full text-sm">
            <thead>
                <tr class="info text-left">
                    <th class="p-3">{{ __m('Staff', 'RenCommissions') }}</th>
                    <th class="p-3">{{ __m('Total', 'RenCommissions') }}</th>
                    <th class="p-3">{{ __m('Pending', 'RenCommissions') }}</th>
                    <th class="p-3">{{ __m('Paid', 'RenCommissions') }}</th>
                    <th class="p-3">{{ __m('Count', 'RenCommissions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr class="border-t">
                        <td class="p-3 font-semibold">{{ $row->earner?->username ?? '-' }}</td>
                        <td class="p-3">{{ ns()->currency->define($row->total_amount)->format() }}</td>
                        <td class="p-3 text-orange-600">{{ ns()->currency->define($row->pending_amount)->format() }}</td>
                        <td class="p-3 text-emerald-600">{{ ns()->currency->define($row->paid_amount)->format() }}</td>
                        <td class="p-3">{{ (int) $row->rows_count }}</td>
                    </tr>
                @empty
                    <tr><td class="p-4 text-secondary" colspan="5">{{ __m('No data for this period.', 'RenCommissions') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4 border-t">{{ $rows->links() }}</div>
    </div>
</div>
</div>
@endsection
