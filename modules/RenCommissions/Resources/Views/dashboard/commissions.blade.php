@extends('layout.dashboard')

@section('layout.dashboard.body')
<div>
    @include('RenCommissions::dashboard.partials.header')

    <div class="ns-box border">
        <form method="GET" class="p-4 border-b grid grid-cols-1 lg:grid-cols-12 gap-3">
            <input name="search" value="{{ $search }}" class="lg:col-span-5 ns-input" placeholder="{{ __m('Search order/product/earner', 'RenCommissions') }}">
            <select name="status" class="lg:col-span-3 ns-select">
                <option value="">{{ __m('All Statuses', 'RenCommissions') }}</option>
                @foreach($statuses as $entry)
                    <option value="{{ $entry }}" @selected($status === $entry)>{{ ucfirst($entry) }}</option>
                @endforeach
            </select>
            <select name="period" class="lg:col-span-2 ns-select">
                @foreach($periodOptions as $key => $label)
                    <option value="{{ $key }}" @selected($period === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="lg:col-span-2 ns-button info">{{ __m('Apply', 'RenCommissions') }}</button>
        </form>

        <div class="overflow-auto">
            <table class="table ns-table w-full text-sm">
                <thead>
                    <tr class="info text-left">
                        <th class="p-3">{{ __m('Date', 'RenCommissions') }}</th>
                        <th class="p-3">{{ __m('Order', 'RenCommissions') }}</th>
                        <th class="p-3">{{ __m('Product', 'RenCommissions') }}</th>
                        <th class="p-3">{{ __m('Earner', 'RenCommissions') }}</th>
                        <th class="p-3">{{ __m('Type', 'RenCommissions') }}</th>
                        <th class="p-3">{{ __m('Amount', 'RenCommissions') }}</th>
                        <th class="p-3">{{ __m('Status', 'RenCommissions') }}</th>
                        <th class="p-3">{{ __m('Action', 'RenCommissions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr class="border-t">
                            <td class="p-3">{{ $row->created_at?->toDateString() }}</td>
                            <td class="p-3">{{ $row->order?->code ?? '-' }}</td>
                            <td class="p-3">{{ $row->product?->name ?? '-' }}</td>
                            <td class="p-3">{{ $row->earner?->username ?? '-' }}</td>
                            <td class="p-3">{{ $row->type?->name ?? ucfirst($row->commission_method) }}</td>
                            <td class="p-3 font-semibold">{{ ns()->currency->define($row->total_commission)->format() }}</td>
                            <td class="p-3 capitalize">{{ $row->status }}</td>
                            <td class="p-3">
                                <div class="flex gap-2">
                                    @if($row->status === 'pending')
                                        <form method="POST" action="{{ ns()->route('rencommissions.commissions.mark-paid', ['commission' => $row->id]) }}">
                                            @csrf
                                            <button class="text-info-secondary">{{ __m('Mark Paid', 'RenCommissions') }}</button>
                                        </form>
                                        <form method="POST" action="{{ ns()->route('rencommissions.commissions.void', ['commission' => $row->id]) }}">
                                            @csrf
                                            <button class="text-error-secondary">{{ __m('Void', 'RenCommissions') }}</button>
                                        </form>
                                    @else
                                        <span class="text-secondary">-</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="p-4 text-secondary" colspan="8">{{ __m('No commissions found.', 'RenCommissions') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t">
            {{ $rows->links() }}
        </div>
    </div>
</div>
</div>
@endsection
