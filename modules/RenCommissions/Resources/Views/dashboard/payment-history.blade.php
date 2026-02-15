@extends('layout.dashboard')

@section('layout.dashboard.body')
<div>
    @include('RenCommissions::dashboard.partials.header')

    <div class="ns-box border overflow-auto">
        <table class="table ns-table w-full text-sm">
            <thead>
                <tr class="info text-left">
                    <th class="p-3">{{ __m('Reference', 'RenCommissions') }}</th>
                    <th class="p-3">{{ __m('Period Start', 'RenCommissions') }}</th>
                    <th class="p-3">{{ __m('Period End', 'RenCommissions') }}</th>
                    <th class="p-3">{{ __m('Entries', 'RenCommissions') }}</th>
                    <th class="p-3">{{ __m('Amount', 'RenCommissions') }}</th>
                    <th class="p-3">{{ __m('Status', 'RenCommissions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr class="border-t">
                        <td class="p-3 font-semibold">{{ $row->reference }}</td>
                        <td class="p-3">{{ optional($row->period_start)->toDateTimeString() }}</td>
                        <td class="p-3">{{ optional($row->period_end)->toDateTimeString() }}</td>
                        <td class="p-3">{{ $row->entries_count }}</td>
                        <td class="p-3">{{ ns()->currency->define($row->total_amount)->format() }}</td>
                        <td class="p-3 capitalize">{{ $row->status }}</td>
                    </tr>
                @empty
                    <tr><td class="p-4 text-secondary" colspan="6">{{ __m('No payment history.', 'RenCommissions') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4 border-t">{{ $rows->links() }}</div>
    </div>
</div>
</div>
@endsection
