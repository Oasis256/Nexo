@php
    $currentPeriod = $period ?? 'this_month';
@endphp

@include( Hook::filter( 'ns-dashboard-header-file', '../common/dashboard-header' ) )
<div id="dashboard-content" class="px-4 pb-6">
    @include('common.dashboard.title')
    <div class="mb-4 rounded-lg ns-box border border-box-edge">
        <div class="p-3 md:p-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-4 text-sm">
                <a href="{{ ns()->route('rencommissions.dashboard', ['period' => $currentPeriod]) }}"
                    class="{{ request()->routeIs('rencommissions.dashboard') ? 'font-semibold text-info-primary' : 'text-secondary hover:text-primary' }}">
                    {{ __m('Dashboard', 'RenCommissions') }}
                </a>
                <a href="{{ ns()->route('rencommissions.commissions', ['period' => $currentPeriod]) }}"
                    class="{{ request()->routeIs('rencommissions.commissions') ? 'font-semibold text-info-primary' : 'text-secondary hover:text-primary' }}">
                    {{ __m('All Commissions', 'RenCommissions') }}
                </a>
                <a href="{{ ns()->route('rencommissions.staff', ['period' => $currentPeriod]) }}"
                    class="{{ request()->routeIs('rencommissions.staff') ? 'font-semibold text-info-primary' : 'text-secondary hover:text-primary' }}">
                    {{ __m('Staff Earnings', 'RenCommissions') }}
                </a>
                <a href="{{ ns()->route('rencommissions.pending', ['period' => $currentPeriod]) }}"
                    class="{{ request()->routeIs('rencommissions.pending') ? 'font-semibold text-info-primary' : 'text-secondary hover:text-primary' }}">
                    {{ __m('Pending Payouts', 'RenCommissions') }}
                </a>
                <a href="{{ ns()->route('rencommissions.payout-interface') }}"
                    class="{{ request()->routeIs('rencommissions.payout-interface') ? 'font-semibold text-info-primary' : 'text-secondary hover:text-primary' }}">
                    {{ __m('Payout Interface', 'RenCommissions') }}
                </a>
                <a href="{{ ns()->route('rencommissions.history') }}"
                    class="{{ request()->routeIs('rencommissions.history') ? 'font-semibold text-info-primary' : 'text-secondary hover:text-primary' }}">
                    {{ __m('Payment History', 'RenCommissions') }}
                </a>
                <a href="{{ ns()->route('rencommissions.types') }}"
                    class="{{ request()->routeIs('rencommissions.types') ? 'font-semibold text-info-primary' : 'text-secondary hover:text-primary' }}">
                    {{ __m('Commission Types', 'RenCommissions') }}
                </a>
            </div>
            @if (isset($periodOptions) && count($periodOptions))
                <div class="flex flex-wrap gap-2">
                    @foreach ($periodOptions as $key => $label)
                        @php
                            $params = request()->query();
                            $params['period'] = $key;
                        @endphp
                        <a href="{{ request()->url() . '?' . http_build_query($params) }}"
                            class="px-2 py-1 rounded border border-box-edge text-xs {{ $currentPeriod === $key ? 'bg-box-edge text-primary font-semibold' : 'text-secondary hover:text-primary' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
    <div class="mb-4">
        <div class="-mx-2 md:-mx-3 flex flex-wrap">
            <div class="px-2 md:px-3 w-full md:w-1/2 xl:w-1/4 mb-3">
                <div class="rounded-lg shadow p-3 bg-gradient-to-br from-green-400 to-green-600">
                    <h3 class="text-white font-semibold">{{ __m('Total', 'RenCommissions') }}</h3>
                    <p class="text-white font-bold text-5xl">{{ $summary['total']['formatted'] ?? ns()->currency->define(0)->format() }}</p>
                    <div class="w-full flex justify-end">
                        <span class="text-xs text-green-100">{{ ($summary['total']['count'] ?? 0) . ' ' . __m('records', 'RenCommissions') }}</span>
                    </div>
                </div>
            </div>
            <div class="px-2 md:px-3 w-full md:w-1/2 xl:w-1/4 mb-3">
                <div class="rounded-lg shadow p-3 bg-gradient-to-br from-indigo-400 to-indigo-600">
                    <h3 class="text-white font-semibold">{{ __m('Pending', 'RenCommissions') }}</h3>
                    <p class="text-white font-bold text-5xl">{{ $summary['pending']['formatted'] ?? ns()->currency->define(0)->format() }}</p>
                    <div class="w-full flex justify-end">
                        <span class="text-xs text-indigo-100">{{ ($summary['pending']['count'] ?? 0) . ' ' . __m('pending', 'RenCommissions') }}</span>
                    </div>
                </div>
            </div>
            <div class="px-2 md:px-3 w-full md:w-1/2 xl:w-1/4 mb-3">
                <div class="rounded-lg shadow p-3 bg-gradient-to-br from-blue-400 to-blue-600">
                    <h3 class="text-white font-semibold">{{ __m('Paid', 'RenCommissions') }}</h3>
                    <p class="text-white font-bold text-5xl">{{ $summary['paid']['formatted'] ?? ns()->currency->define(0)->format() }}</p>
                    <div class="w-full flex justify-end">
                        <span class="text-xs text-blue-100">{{ ($summary['paid']['count'] ?? 0) . ' ' . __m('paid', 'RenCommissions') }}</span>
                    </div>
                </div>
            </div>
            <div class="px-2 md:px-3 w-full md:w-1/2 xl:w-1/4 mb-3">
                <div class="rounded-lg shadow p-3 bg-gradient-to-br from-cyan-500 to-sky-600">
                    <h3 class="text-white font-semibold">{{ __m('Average', 'RenCommissions') }}</h3>
                    <p class="text-white font-bold text-5xl">{{ $summary['average']['formatted'] ?? ns()->currency->define(0)->format() }}</p>
                    <div class="w-full flex justify-end">
                        <span class="text-xs text-cyan-100">{{ __m('per line item', 'RenCommissions') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
