<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ __m('Gift Voucher', 'GiftVouchers') }} - {{ $voucher->code }}</title>
    <style>
        @page {
            size: 3.5in 2in;
            margin: 0;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background: #f0f0f0;
        }
        
        .voucher-card {
            width: 3.5in;
            height: 2in;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            position: relative;
            overflow: hidden;
            box-sizing: border-box;
            padding: 12px;
        }
        
        .voucher-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
        }
        
        .voucher-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        
        .store-logo {
            max-height: 24px;
            max-width: 80px;
        }
        
        .voucher-title {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 2px;
            opacity: 0.9;
        }
        
        .voucher-body {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        
        .qr-section {
            text-align: center;
        }
        
        .qr-code {
            width: 70px;
            height: 70px;
            background: white;
            padding: 4px;
            border-radius: 4px;
        }
        
        .scan-instruction {
            font-size: 7px;
            margin-top: 4px;
            opacity: 0.8;
        }
        
        .voucher-details {
            flex: 1;
        }
        
        .voucher-code {
            font-family: monospace;
            font-size: 14px;
            font-weight: bold;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }
        
        .voucher-value {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 4px;
        }
        
        .valid-until {
            font-size: 9px;
            opacity: 0.9;
        }
        
        .voucher-footer {
            position: absolute;
            bottom: 8px;
            left: 12px;
            right: 12px;
        }
        
        .terms {
            font-size: 6px;
            opacity: 0.7;
            text-align: center;
        }
        
        @media print {
            body {
                background: white;
            }
            
            .voucher-card {
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="voucher-card">
        <div class="voucher-header">
            @if($storeLogo ?? false)
                <img src="{{ $storeLogo }}" alt="{{ $storeName ?? 'Store' }}" class="store-logo">
            @else
                <span style="font-weight: bold; font-size: 12px;">{{ $storeName ?? 'Gift Voucher' }}</span>
            @endif
            <span class="voucher-title">{{ __m('Gift Voucher', 'GiftVouchers') }}</span>
        </div>
        
        <div class="voucher-body">
            <div class="qr-section">
                <img src="{{ $qrImageBase64 }}" alt="Scan to Redeem" class="qr-code">
                <p class="scan-instruction">{{ __m('Scan to Redeem', 'GiftVouchers') }}</p>
            </div>
            
            <div class="voucher-details">
                <p class="voucher-code">{{ $voucher->code }}</p>
                <p class="voucher-value">{{ ns()->currency->define($voucher->total_value)->format() }}</p>
                <p class="valid-until">
                    {{ __m('Valid Until:', 'GiftVouchers') }} 
                    {{ $voucher->expires_at?->format('d M Y') ?? __m('No Expiry', 'GiftVouchers') }}
                </p>
            </div>
        </div>
        
        <div class="voucher-footer">
            <p class="terms">{{ __m('Terms & conditions apply. Non-refundable. Present this card at point of sale.', 'GiftVouchers') }}</p>
        </div>
    </div>
</body>
</html>
