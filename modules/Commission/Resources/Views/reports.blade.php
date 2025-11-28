@extends('layout.dashboard')

@section('layout.dashboard.body')
<div class="h-full flex-auto flex flex-col">
    @include('common.dashboard-header')
    <div class="px-4 flex-auto flex flex-col">
        <div class="page-inner-header mb-4">
            <h3 class="text-3xl text-primary font-bold">{{ $title ?? __m('Commission Reports', 'Commission') }}</h3>
            <p class="text-secondary">{{ $description ?? __m('Generate and export commission reports.', 'Commission') }}</p>
        </div>

        <!-- Date Range Filter -->
        <div class="flex justify-between items-center mb-4">
            <div class="flex gap-4">
                <div class="flex items-center gap-2">
                    <label class="text-sm text-secondary">{{ __m('From:', 'Commission') }}</label>
                    <input 
                        type="date" 
                        name="start_date" 
                        value="{{ $dateRange['start'] }}" 
                        class="border border-box-edge rounded px-3 py-2 text-primary bg-box-background"
                    />
                </div>
                <div class="flex items-center gap-2">
                    <label class="text-sm text-secondary">{{ __m('To:', 'Commission') }}</label>
                    <input 
                        type="date" 
                        name="end_date" 
                        value="{{ \Carbon\Carbon::parse($dateRange['end'])->format('Y-m-d') }}" 
                        class="border border-box-edge rounded px-3 py-2 text-primary bg-box-background"
                    />
                </div>
                <button 
                    type="button" 
                    onclick="applyDateFilter()" 
                    class="px-4 py-2 bg-info-tertiary text-white rounded hover:bg-info-secondary"
                >
                    {{ __m('Apply', 'Commission') }}
                </button>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('commission.export.user-summary', ['start_date' => $dateRange['start'], 'end_date' => $dateRange['end']]) }}" class="px-4 py-2 bg-info-tertiary text-white rounded hover:bg-info-secondary">
                    <i class="las la-file-csv mr-1"></i>
                    {{ __m('User Summary', 'Commission') }}
                </a>
                <a href="{{ route('commission.export.payroll', ['start_date' => $dateRange['start'], 'end_date' => $dateRange['end']]) }}" class="px-4 py-2 bg-success-tertiary text-white rounded hover:bg-success-secondary">
                    <i class="las la-file-export mr-1"></i>
                    {{ __m('Payroll Export', 'Commission') }}
                </a>
            </div>
        </div>

        <!-- Payroll Report Table -->
        <div class="bg-box-background border border-box-edge rounded-lg shadow-sm">
            <div class="px-6 py-4 border-b border-box-edge flex justify-between items-center">
                <h5 class="text-lg font-semibold text-primary">{{ __m('Payroll Summary', 'Commission') }}</h5>
                <span class="text-sm text-secondary">
                    {{ __m('Period:', 'Commission') }} {{ $dateRange['start'] }} - {{ \Carbon\Carbon::parse($dateRange['end'])->format('Y-m-d') }}
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-surface border-b border-box-edge">
                            <th class="px-6 py-3 text-left text-xs font-medium text-secondary uppercase tracking-wider">
                                {{ __m('User', 'Commission') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-secondary uppercase tracking-wider">
                                {{ __m('Email', 'Commission') }}
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-secondary uppercase tracking-wider">
                                {{ __m('Total Commissions', 'Commission') }}
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-secondary uppercase tracking-wider">
                                {{ __m('Total Amount', 'Commission') }}
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-secondary uppercase tracking-wider">
                                {{ __m('Actions', 'Commission') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-box-edge">
                        @forelse($payrollReport as $row)
                            <tr class="hover:bg-surface transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 rounded-full bg-info-tertiary text-white flex items-center justify-center mr-3">
                                            {{ strtoupper(substr($row['username'], 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="font-medium text-primary">{{ $row['username'] }}</p>
                                            @if($row['full_name'])
                                                <p class="text-xs text-secondary">{{ $row['full_name'] }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-secondary">
                                    {{ $row['email'] }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-info-tertiary text-white">
                                        {{ $row['total_commissions'] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="text-lg font-semibold text-success-tertiary">
                                        {{ ns()->currency->define($row['total_amount'])->format() }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <a href="{{ route('commission.reports.user', ['user' => $row['user_id'], 'start_date' => $dateRange['start'], 'end_date' => $dateRange['end']]) }}" 
                                       class="text-info-tertiary hover:text-info-secondary"
                                       title="{{ __m('View Details', 'Commission') }}">
                                        <i class="las la-eye text-xl"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-secondary">
                                    <i class="las la-inbox text-4xl mb-2"></i>
                                    <p>{{ __m('No commission data available for this period.', 'Commission') }}</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if(count($payrollReport) > 0)
                        <tfoot>
                            <tr class="bg-surface border-t-2 border-box-edge font-semibold">
                                <td class="px-6 py-4 text-primary" colspan="2">
                                    {{ __m('Total', 'Commission') }}
                                </td>
                                <td class="px-6 py-4 text-center text-primary">
                                    {{ collect($payrollReport)->sum('total_commissions') }}
                                </td>
                                <td class="px-6 py-4 text-right text-success-tertiary text-lg">
                                    {{ ns()->currency->define(collect($payrollReport)->sum('total_amount'))->format() }}
                                </td>
                                <td class="px-6 py-4"></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('layout.dashboard.footer')
<script>
function applyDateFilter() {
    const startDate = document.querySelector('input[name="start_date"]').value;
    const endDate = document.querySelector('input[name="end_date"]').value;
    window.location.href = `{{ route('commission.reports') }}?start_date=${startDate}&end_date=${endDate}`;
}
</script>
@endsection
