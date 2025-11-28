@extends('layout.dashboard')

@section('layout.dashboard.body')
<div class="h-full flex-auto flex flex-col">
    @include('common.dashboard-header')
    <div class="px-4 flex-auto flex flex-col">
        <div class="page-inner-header mb-4">
            <h3 class="text-3xl text-primary font-bold">{{ $title ?? __m('Commission Dashboard', 'Commission') }}</h3>
            <p class="text-secondary">{{ $description ?? __m('Overview of commission earnings and performance.', 'Commission') }}</p>
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
                <a href="{{ route('commission.export.csv', ['start_date' => $dateRange['start'], 'end_date' => $dateRange['end']]) }}" class="px-4 py-2 bg-success-tertiary text-white rounded hover:bg-success-secondary">
                    {{ __m('Export CSV', 'Commission') }}
                </a>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <!-- Total Earnings Card -->
            <div class="bg-box-background border border-box-edge rounded-lg p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-secondary mb-1">{{ __m('Total Earnings', 'Commission') }}</p>
                        <h4 class="text-2xl font-bold text-primary">
                            {{ ns()->currency->define(collect($topEarners)->sum('total_commission'))->format() }}
                        </h4>
                    </div>
                    <div class="w-12 h-12 bg-success-tertiary rounded-full flex items-center justify-center">
                        <i class="las la-dollar-sign text-2xl text-white"></i>
                    </div>
                </div>
            </div>

            <!-- Total Commissions Card -->
            <div class="bg-box-background border border-box-edge rounded-lg p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-secondary mb-1">{{ __m('Total Commissions', 'Commission') }}</p>
                        <h4 class="text-2xl font-bold text-primary">
                            {{ collect($topEarners)->sum('commission_count') }}
                        </h4>
                    </div>
                    <div class="w-12 h-12 bg-info-tertiary rounded-full flex items-center justify-center">
                        <i class="las la-receipt text-2xl text-white"></i>
                    </div>
                </div>
            </div>

            <!-- Active Earners Card -->
            <div class="bg-box-background border border-box-edge rounded-lg p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-secondary mb-1">{{ __m('Active Earners', 'Commission') }}</p>
                        <h4 class="text-2xl font-bold text-primary">
                            {{ count($topEarners) }}
                        </h4>
                    </div>
                    <div class="w-12 h-12 bg-warning-tertiary rounded-full flex items-center justify-center">
                        <i class="las la-users text-2xl text-white"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Top Earners -->
            <div class="bg-box-background border border-box-edge rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-box-edge">
                    <h5 class="text-lg font-semibold text-primary">{{ __m('Top Earners', 'Commission') }}</h5>
                </div>
                <div class="p-6">
                    @if(count($topEarners) > 0)
                        <div class="space-y-4">
                            @foreach($topEarners as $index => $earner)
                                <div class="flex items-center justify-between py-2 {{ !$loop->last ? 'border-b border-box-edge' : '' }}">
                                    <div class="flex items-center gap-3">
                                        <span class="w-8 h-8 rounded-full bg-info-tertiary text-white flex items-center justify-center text-sm font-bold">
                                            {{ $index + 1 }}
                                        </span>
                                        <div>
                                            <a href="{{ route('commission.reports.user', ['user' => $earner['user_id']]) }}" class="font-medium text-primary hover:text-info-tertiary">
                                                {{ $earner['username'] }}
                                            </a>
                                            <p class="text-xs text-secondary">{{ $earner['commission_count'] }} {{ __m('commissions', 'Commission') }}</p>
                                        </div>
                                    </div>
                                    <span class="text-lg font-semibold text-success-tertiary">
                                        {{ ns()->currency->define($earner['total_commission'])->format() }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-center text-secondary py-8">{{ __m('No commission data available for this period.', 'Commission') }}</p>
                    @endif
                </div>
            </div>

            <!-- Recent Commissions -->
            <div class="bg-box-background border border-box-edge rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-box-edge">
                    <h5 class="text-lg font-semibold text-primary">{{ __m('Recent Commissions', 'Commission') }}</h5>
                </div>
                <div class="p-6">
                    @if(count($recentCommissions) > 0)
                        <div class="space-y-3">
                            @foreach($recentCommissions as $commission)
                                <div class="flex items-center justify-between py-2 {{ !$loop->last ? 'border-b border-box-edge' : '' }}">
                                    <div>
                                        <p class="font-medium text-primary">{{ $commission['user'] }}</p>
                                        <p class="text-xs text-secondary">
                                            {{ $commission['product'] }} • 
                                            {{ \Carbon\Carbon::parse($commission['created_at'])->diffForHumans() }}
                                        </p>
                                    </div>
                                    <span class="text-sm font-semibold text-success-tertiary">
                                        +{{ ns()->currency->define($commission['value'])->format() }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-center text-secondary py-8">{{ __m('No recent commissions.', 'Commission') }}</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Daily Earnings Chart -->
        <div class="mt-6 bg-box-background border border-box-edge rounded-lg shadow-sm">
            <div class="px-6 py-4 border-b border-box-edge">
                <h5 class="text-lg font-semibold text-primary">{{ __m('Daily Earnings', 'Commission') }}</h5>
            </div>
            <div class="p-6">
                <div id="daily-earnings-chart" style="height: 300px;">
                    <!-- Chart will be rendered here by Vue component -->
                    <ns-commission-daily-chart :data="{{ json_encode($dailyEarnings) }}"></ns-commission-daily-chart>
                </div>
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
    window.location.href = `{{ route('commission.dashboard') }}?start_date=${startDate}&end_date=${endDate}`;
}
</script>
@endsection
