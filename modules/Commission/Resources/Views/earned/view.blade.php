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
                <a href="{{ route('commission.earned.list') }}" class="px-4 py-2 bg-surface border border-box-edge rounded text-primary hover:bg-box-background">
                    <i class="las la-arrow-left mr-1"></i>
                    {{ __m('Back to List', 'Commission') }}
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Commission Details -->
            <div class="bg-box-background border border-box-edge rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-box-edge">
                    <h5 class="text-lg font-semibold text-primary">{{ __m('Commission Details', 'Commission') }}</h5>
                </div>
                <div class="p-6">
                    <dl class="space-y-4">
                        <div class="flex justify-between py-2 border-b border-box-edge">
                            <dt class="text-secondary">{{ __m('Amount Earned', 'Commission') }}</dt>
                            <dd class="text-xl font-bold text-success-tertiary">
                                {{ ns()->currency->define($earnedCommission->amount)->format() }}
                            </dd>
                        </div>
                        <div class="flex justify-between py-2 border-b border-box-edge">
                            <dt class="text-secondary">{{ __m('Commission Type', 'Commission') }}</dt>
                            <dd class="text-primary">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                    @if($earnedCommission->commission?->type === 'on_the_house') bg-warning-tertiary text-white
                                    @elseif($earnedCommission->commission?->type === 'fixed') bg-info-tertiary text-white
                                    @else bg-success-tertiary text-white @endif">
                                    {{ $earnedCommission->commission?->name ?? __m('Unknown', 'Commission') }}
                                </span>
                            </dd>
                        </div>
                        <div class="flex justify-between py-2 border-b border-box-edge">
                            <dt class="text-secondary">{{ __m('Rate Applied', 'Commission') }}</dt>
                            <dd class="text-primary">{{ $earnedCommission->rate ?? 'N/A' }}</dd>
                        </div>
                        <div class="flex justify-between py-2 border-b border-box-edge">
                            <dt class="text-secondary">{{ __m('Calculation Base', 'Commission') }}</dt>
                            <dd class="text-primary">{{ $earnedCommission->calculation_base ?? 'N/A' }}</dd>
                        </div>
                        <div class="flex justify-between py-2">
                            <dt class="text-secondary">{{ __m('Created At', 'Commission') }}</dt>
                            <dd class="text-primary">{{ $earnedCommission->created_at->format('M d, Y H:i:s') }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- User Details -->
            <div class="bg-box-background border border-box-edge rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-box-edge">
                    <h5 class="text-lg font-semibold text-primary">{{ __m('Recipient', 'Commission') }}</h5>
                </div>
                <div class="p-6">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-16 h-16 rounded-full bg-info-tertiary text-white flex items-center justify-center text-2xl font-bold">
                            {{ strtoupper(substr($earnedCommission->user?->username ?? 'U', 0, 1)) }}
                        </div>
                        <div>
                            <h4 class="text-xl font-semibold text-primary">
                                {{ $earnedCommission->user?->username ?? __m('Unknown User', 'Commission') }}
                            </h4>
                            <p class="text-secondary">{{ $earnedCommission->user?->email }}</p>
                        </div>
                    </div>
                    <a href="{{ route('commission.reports.user', ['user' => $earnedCommission->user_id]) }}" 
                       class="inline-flex items-center px-4 py-2 bg-info-tertiary text-white rounded hover:bg-info-secondary">
                        <i class="las la-chart-line mr-2"></i>
                        {{ __m('View User Report', 'Commission') }}
                    </a>
                </div>
            </div>

            <!-- Order Details -->
            <div class="bg-box-background border border-box-edge rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-box-edge">
                    <h5 class="text-lg font-semibold text-primary">{{ __m('Order Information', 'Commission') }}</h5>
                </div>
                <div class="p-6">
                    <dl class="space-y-4">
                        <div class="flex justify-between py-2 border-b border-box-edge">
                            <dt class="text-secondary">{{ __m('Order Code', 'Commission') }}</dt>
                            <dd class="text-primary">
                                <a href="{{ ns()->route('ns.dashboard.orders.view', ['id' => $earnedCommission->order_id]) }}" 
                                   class="text-info-tertiary hover:underline">
                                    #{{ $earnedCommission->order?->code ?? $earnedCommission->order_id }}
                                </a>
                            </dd>
                        </div>
                        <div class="flex justify-between py-2 border-b border-box-edge">
                            <dt class="text-secondary">{{ __m('Order Total', 'Commission') }}</dt>
                            <dd class="text-primary">
                                {{ ns()->currency->define($earnedCommission->order?->total ?? 0)->format() }}
                            </dd>
                        </div>
                        <div class="flex justify-between py-2">
                            <dt class="text-secondary">{{ __m('Order Date', 'Commission') }}</dt>
                            <dd class="text-primary">
                                {{ $earnedCommission->order?->created_at?->format('M d, Y H:i') ?? 'N/A' }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Product Details -->
            <div class="bg-box-background border border-box-edge rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-box-edge">
                    <h5 class="text-lg font-semibold text-primary">{{ __m('Product Information', 'Commission') }}</h5>
                </div>
                <div class="p-6">
                    <dl class="space-y-4">
                        <div class="flex justify-between py-2 border-b border-box-edge">
                            <dt class="text-secondary">{{ __m('Product Name', 'Commission') }}</dt>
                            <dd class="text-primary">
                                {{ $earnedCommission->product?->name ?? __m('Unknown Product', 'Commission') }}
                            </dd>
                        </div>
                        <div class="flex justify-between py-2 border-b border-box-edge">
                            <dt class="text-secondary">{{ __m('Quantity Sold', 'Commission') }}</dt>
                            <dd class="text-primary">
                                {{ $earnedCommission->orderProduct?->quantity ?? 'N/A' }}
                            </dd>
                        </div>
                        <div class="flex justify-between py-2 border-b border-box-edge">
                            <dt class="text-secondary">{{ __m('Unit Price', 'Commission') }}</dt>
                            <dd class="text-primary">
                                {{ ns()->currency->define($earnedCommission->orderProduct?->unit_price ?? 0)->format() }}
                            </dd>
                        </div>
                        <div class="flex justify-between py-2">
                            <dt class="text-secondary">{{ __m('Line Total', 'Commission') }}</dt>
                            <dd class="text-primary">
                                {{ ns()->currency->define($earnedCommission->orderProduct?->total_price ?? 0)->format() }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
