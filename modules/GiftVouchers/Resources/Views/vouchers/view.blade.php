@extends('layout.dashboard')

@section('layout.dashboard.body')
<div class="h-full flex-auto flex flex-col">
    @include('common.dashboard-header', [
        'title' => $title ?? __('View Voucher'),
        'description' => $description ?? __('Gift voucher details'),
    ])
    
    <div class="px-4 flex-auto flex flex-col overflow-y-auto">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            {{-- Main Info Card --}}
            <div class="lg:col-span-2">
                <div class="ns-box rounded-lg shadow">
                    <div class="ns-box-header p-4 border-b">
                        <h3 class="text-lg font-semibold flex items-center gap-2">
                            <i class="las la-gift text-xl"></i>
                            {{ __m('Voucher Details', 'GiftVouchers') }}
                        </h3>
                    </div>
                    <div class="ns-box-body p-4">
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                            <div>
                                <p class="text-sm text-secondary">{{ __m('Voucher Code', 'GiftVouchers') }}</p>
                                <p class="font-mono font-bold text-lg">{{ $voucher->code }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-secondary">{{ __m('Status', 'GiftVouchers') }}</p>
                                <p class="font-semibold">
                                    <span class="px-2 py-1 rounded text-sm
                                        @if($voucher->status === 'active') bg-green-100 text-green-800
                                        @elseif($voucher->status === 'partially_redeemed') bg-blue-100 text-blue-800
                                        @elseif($voucher->status === 'fully_redeemed') bg-gray-100 text-gray-800
                                        @elseif($voucher->status === 'expired') bg-yellow-100 text-yellow-800
                                        @else bg-red-100 text-red-800
                                        @endif
                                    ">
                                        {{ ucfirst(str_replace('_', ' ', $voucher->status)) }}
                                    </span>
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-secondary">{{ __m('Template', 'GiftVouchers') }}</p>
                                <p class="font-semibold">{{ $voucher->template?->name ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-secondary">{{ __m('Total Value', 'GiftVouchers') }}</p>
                                <p class="font-bold text-xl text-success-primary">
                                    {{ ns()->currency->define($voucher->total_value)->format() }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-secondary">{{ __m('Remaining Value', 'GiftVouchers') }}</p>
                                <p class="font-bold text-xl">
                                    {{ ns()->currency->define($voucher->remaining_value)->format() }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-secondary">{{ __m('Redeemed', 'GiftVouchers') }}</p>
                                <p class="font-semibold">
                                    {{ number_format($voucher->redemption_percentage, 1) }}%
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-secondary">{{ __m('Purchaser', 'GiftVouchers') }}</p>
                                <p class="font-semibold">
                                    @if($voucher->purchaser)
                                        {{ $voucher->purchaser->first_name }} {{ $voucher->purchaser->last_name }}
                                    @else
                                        <span class="text-secondary">-</span>
                                    @endif
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-secondary">{{ __m('Created', 'GiftVouchers') }}</p>
                                <p class="font-semibold">{{ $voucher->created_at->format('d M Y H:i') }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-secondary">{{ __m('Expires', 'GiftVouchers') }}</p>
                                <p class="font-semibold @if($voucher->isExpired()) text-danger-primary @endif">
                                    {{ $voucher->expires_at?->format('d M Y') ?? '-' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Items --}}
                <div class="ns-box rounded-lg shadow mt-4">
                    <div class="ns-box-header p-4 border-b">
                        <h3 class="text-lg font-semibold flex items-center gap-2">
                            <i class="las la-list text-xl"></i>
                            {{ __m('Voucher Items', 'GiftVouchers') }}
                        </h3>
                    </div>
                    <div class="ns-box-body">
                        <table class="table ns-table w-full">
                            <thead>
                                <tr>
                                    <th class="p-2 text-left">{{ __m('Product', 'GiftVouchers') }}</th>
                                    <th class="p-2 text-right">{{ __m('Quantity', 'GiftVouchers') }}</th>
                                    <th class="p-2 text-right">{{ __m('Remaining', 'GiftVouchers') }}</th>
                                    <th class="p-2 text-right">{{ __m('Unit Price', 'GiftVouchers') }}</th>
                                    <th class="p-2 text-right">{{ __m('Total', 'GiftVouchers') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($voucher->items as $item)
                                <tr class="border-b">
                                    <td class="p-2">{{ $item->product?->name ?? '-' }}</td>
                                    <td class="p-2 text-right">{{ $item->quantity }}</td>
                                    <td class="p-2 text-right">{{ $item->quantity_remaining }}</td>
                                    <td class="p-2 text-right">{{ ns()->currency->define($item->unit_price)->format() }}</td>
                                    <td class="p-2 text-right font-semibold">{{ ns()->currency->define($item->total_price)->format() }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Redemption History --}}
                @if($voucher->redemptions->count() > 0)
                <div class="ns-box rounded-lg shadow mt-4">
                    <div class="ns-box-header p-4 border-b">
                        <h3 class="text-lg font-semibold flex items-center gap-2">
                            <i class="las la-history text-xl"></i>
                            {{ __m('Redemption History', 'GiftVouchers') }}
                        </h3>
                    </div>
                    <div class="ns-box-body">
                        <table class="table ns-table w-full">
                            <thead>
                                <tr>
                                    <th class="p-2 text-left">{{ __m('Date', 'GiftVouchers') }}</th>
                                    <th class="p-2 text-left">{{ __m('Items', 'GiftVouchers') }}</th>
                                    <th class="p-2 text-right">{{ __m('Value', 'GiftVouchers') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($voucher->redemptions as $redemption)
                                <tr class="border-b">
                                    <td class="p-2">{{ $redemption->created_at->format('d M Y H:i') }}</td>
                                    <td class="p-2">{{ $redemption->items->count() }} items</td>
                                    <td class="p-2 text-right font-semibold">{{ ns()->currency->define($redemption->total_value)->format() }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif
            </div>

            {{-- QR Code Card --}}
            <div class="lg:col-span-1">
                <div class="ns-box rounded-lg shadow">
                    <div class="ns-box-header p-4 border-b">
                        <h3 class="text-lg font-semibold flex items-center gap-2">
                            <i class="las la-qrcode text-xl"></i>
                            {{ __m('QR Code', 'GiftVouchers') }}
                        </h3>
                    </div>
                    <div class="ns-box-body p-4 text-center">
                        @if($voucher->isRedeemable())
                            <img src="{{ $qrBase64 }}" alt="QR Code" class="mx-auto mb-4" style="width: 200px; height: 200px;">
                            <p class="text-sm text-secondary mb-4">
                                {{ __m('Scan this QR code to redeem the voucher', 'GiftVouchers') }}
                            </p>
                            <div class="flex gap-2 justify-center">
                                <button onclick="window.print()" class="ns-button info">
                                    <i class="las la-print mr-1"></i>
                                    {{ __m('Print', 'GiftVouchers') }}
                                </button>
                            </div>
                        @else
                            <div class="py-8 text-secondary">
                                <i class="las la-ban text-4xl mb-2"></i>
                                <p>{{ __m('This voucher is no longer redeemable', 'GiftVouchers') }}</p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Actions --}}
                <div class="ns-box rounded-lg shadow mt-4">
                    <div class="ns-box-header p-4 border-b">
                        <h3 class="text-lg font-semibold">{{ __m('Actions', 'GiftVouchers') }}</h3>
                    </div>
                    <div class="ns-box-body p-4">
                        <div class="flex flex-col gap-2">
                            <a href="{{ ns()->url('dashboard/gift-vouchers/edit/' . $voucher->id) }}" class="ns-button info w-full text-center">
                                <i class="las la-edit mr-1"></i>
                                {{ __m('Edit Voucher', 'GiftVouchers') }}
                            </a>
                            @if($voucher->isRedeemable())
                            <button class="ns-button warning w-full" @click="regenerateQr()">
                                <i class="las la-sync mr-1"></i>
                                {{ __m('Regenerate QR', 'GiftVouchers') }}
                            </button>
                            @endif
                            <a href="{{ ns()->url('dashboard/gift-vouchers') }}" class="ns-button default w-full text-center">
                                <i class="las la-arrow-left mr-1"></i>
                                {{ __m('Back to List', 'GiftVouchers') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
