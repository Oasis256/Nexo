@extends('layout.dashboard')

@section('layout.dashboard.body')
<div class="h-full flex-auto flex flex-col">
    @include('common.dashboard-header')
    <div class="px-4 flex-auto flex flex-col">
        <div class="page-inner-header mb-4">
            <div class="flex justify-between items-start">
                <div>
                    <h3 class="text-3xl text-primary font-bold">{{ $title }}</h3>
                    <p class="text-secondary">{{ $description }}</p>
                </div>
                <a href="{{ route('commission.reports') }}" class="px-4 py-2 bg-surface border border-box-edge rounded text-primary hover:bg-box-background">
                    <i class="las la-arrow-left mr-1"></i>
                    {{ __m('Back to Reports', 'Commission') }}
                </a>
            </div>
        </div>

        <!-- User Info Card -->
        <div class="bg-box-background border border-box-edge rounded-lg p-6 shadow-sm mb-6">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-full bg-info-tertiary text-white flex items-center justify-center text-2xl font-bold">
                    {{ strtoupper(substr($user->username, 0, 1)) }}
                </div>
                <div class="flex-1">
                    <h4 class="text-xl font-semibold text-primary">{{ $user->username }}</h4>
                    <p class="text-secondary">{{ $user->email }}</p>
                    @if($user->attribute?->first_name || $user->attribute?->last_name)
                        <p class="text-sm text-secondary">
                            {{ $user->attribute?->first_name }} {{ $user->attribute?->last_name }}
                        </p>
                    @endif
                </div>
                <div class="text-right">
                    <p class="text-sm text-secondary">{{ __m('Period:', 'Commission') }}</p>
                    <p class="text-primary font-medium">
                        {{ $dateRange['start'] }} - {{ \Carbon\Carbon::parse($dateRange['end'])->format('Y-m-d') }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-box-background border border-box-edge rounded-lg p-4 shadow-sm">
                <p class="text-sm text-secondary mb-1">{{ __m('Total Earned', 'Commission') }}</p>
                <h4 class="text-2xl font-bold text-success-tertiary">
                    {{ ns()->currency->define($summary['total_amount'] ?? 0)->format() }}
                </h4>
            </div>
            <div class="bg-box-background border border-box-edge rounded-lg p-4 shadow-sm">
                <p class="text-sm text-secondary mb-1">{{ __m('Total Commissions', 'Commission') }}</p>
                <h4 class="text-2xl font-bold text-primary">
                    {{ $summary['total_commissions'] ?? 0 }}
                </h4>
            </div>
            <div class="bg-box-background border border-box-edge rounded-lg p-4 shadow-sm">
                <p class="text-sm text-secondary mb-1">{{ __m('Average Per Commission', 'Commission') }}</p>
                <h4 class="text-2xl font-bold text-primary">
                    {{ ns()->currency->define($summary['average_amount'] ?? 0)->format() }}
                </h4>
            </div>
            <div class="bg-box-background border border-box-edge rounded-lg p-4 shadow-sm">
                <p class="text-sm text-secondary mb-1">{{ __m('Orders with Commission', 'Commission') }}</p>
                <h4 class="text-2xl font-bold text-primary">
                    {{ $summary['unique_orders'] ?? 0 }}
                </h4>
            </div>
        </div>

        <!-- Export Button -->
        <div class="flex justify-end mb-4">
            <a href="{{ route('commission.export.csv', ['start_date' => $dateRange['start'], 'end_date' => $dateRange['end'], 'user_id' => $user->id]) }}" 
               class="px-4 py-2 bg-success-tertiary text-white rounded hover:bg-success-secondary">
                <i class="las la-file-csv mr-1"></i>
                {{ __m('Export User Data', 'Commission') }}
            </a>
        </div>

        <!-- Earnings List -->
        <div class="bg-box-background border border-box-edge rounded-lg shadow-sm">
            <div class="px-6 py-4 border-b border-box-edge">
                <h5 class="text-lg font-semibold text-primary">{{ __m('Commission History', 'Commission') }}</h5>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-surface border-b border-box-edge">
                            <th class="px-6 py-3 text-left text-xs font-medium text-secondary uppercase tracking-wider">
                                {{ __m('Date', 'Commission') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-secondary uppercase tracking-wider">
                                {{ __m('Order', 'Commission') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-secondary uppercase tracking-wider">
                                {{ __m('Product', 'Commission') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-secondary uppercase tracking-wider">
                                {{ __m('Commission Type', 'Commission') }}
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-secondary uppercase tracking-wider">
                                {{ __m('Amount', 'Commission') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-box-edge">
                        @forelse($earnings as $earning)
                            <tr class="hover:bg-surface transition-colors">
                                <td class="px-6 py-4 text-sm text-primary">
                                    {{ $earning->created_at->format('M d, Y H:i') }}
                                </td>
                                <td class="px-6 py-4">
                                    <a href="{{ ns()->route('ns.dashboard.orders.view', ['id' => $earning->order_id]) }}" 
                                       class="text-info-tertiary hover:underline">
                                        #{{ $earning->order?->code ?? $earning->order_id }}
                                    </a>
                                </td>
                                <td class="px-6 py-4 text-sm text-primary">
                                    {{ $earning->product?->name ?? __m('Unknown Product', 'Commission') }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                        @if($earning->commission?->type === 'on_the_house') bg-warning-tertiary text-white
                                        @elseif($earning->commission?->type === 'fixed') bg-info-tertiary text-white
                                        @else bg-success-tertiary text-white @endif">
                                        {{ $earning->commission?->name ?? __m('Unknown', 'Commission') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right text-success-tertiary font-semibold">
                                    {{ ns()->currency->define($earning->amount)->format() }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-secondary">
                                    <i class="las la-inbox text-4xl mb-2"></i>
                                    <p>{{ __m('No commissions earned in this period.', 'Commission') }}</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($earnings->hasPages())
                <div class="px-6 py-4 border-t border-box-edge">
                    {{ $earnings->appends(['start_date' => $dateRange['start'], 'end_date' => $dateRange['end']])->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
