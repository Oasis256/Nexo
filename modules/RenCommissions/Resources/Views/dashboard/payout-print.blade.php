<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} - {{ $payout->reference }}</title>
    <style>
        :root {
            --ink: #1f2b47;
            --muted: #5f6b84;
            --line: #d9dfe8;
            --accent: #10b8ae;
            --paper: #ffffff;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #eef1f6;
            color: var(--ink);
            font-family: "Segoe UI", Tahoma, sans-serif;
            font-size: 14px;
            line-height: 1.45;
        }
        .toolbar {
            max-width: 1080px;
            margin: 16px auto 0 auto;
            display: flex;
            justify-content: space-between;
            gap: 12px;
        }
        .btn {
            border: 1px solid var(--line);
            background: #fff;
            color: var(--ink);
            border-radius: 6px;
            padding: 8px 12px;
            text-decoration: none;
            cursor: pointer;
            font-weight: 600;
        }
        .btn.primary {
            border-color: var(--accent);
            background: var(--accent);
            color: #fff;
        }
        .sheet {
            max-width: 1080px;
            margin: 12px auto 24px auto;
            background: var(--paper);
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 24px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 16px;
        }
        .header h1 {
            margin: 0 0 6px 0;
            font-size: 24px;
        }
        .header .sub {
            color: var(--muted);
            margin: 0;
        }
        .meta {
            text-align: right;
            color: var(--muted);
        }
        .meta strong {
            color: var(--ink);
        }
        .summary {
            margin: 12px 0 18px 0;
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
        }
        .card {
            border: 1px solid var(--line);
            border-radius: 6px;
            padding: 10px 12px;
            background: #fafbfd;
        }
        .card .k {
            font-size: 12px;
            color: var(--muted);
        }
        .card .v {
            margin-top: 4px;
            font-size: 18px;
            font-weight: 700;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        th, td {
            border: 1px solid var(--line);
            padding: 10px 8px;
            vertical-align: middle;
        }
        th {
            text-align: left;
            font-size: 12px;
            letter-spacing: .02em;
            color: var(--muted);
            background: #f8fafc;
            text-transform: uppercase;
        }
        .amount {
            text-align: right;
            white-space: nowrap;
            font-weight: 700;
        }
        .signature {
            height: 42px;
            border-bottom: 1px solid #8b95ab;
        }
        .date-line {
            height: 24px;
            border-bottom: 1px solid #8b95ab;
        }
        .earner-meta {
            color: var(--muted);
            font-size: 12px;
            margin-top: 3px;
        }
        .footer-lines {
            margin-top: 26px;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 22px;
        }
        .line {
            padding-top: 40px;
            border-bottom: 1px solid #8b95ab;
            color: var(--muted);
            font-size: 12px;
            text-align: center;
        }
        .notes {
            margin-top: 16px;
            border: 1px dashed var(--line);
            background: #fbfdff;
            border-radius: 6px;
            padding: 10px 12px;
            color: var(--muted);
        }
        @media print {
            body {
                background: #fff;
            }
            .toolbar {
                display: none;
            }
            .sheet {
                margin: 0;
                border: 0;
                border-radius: 0;
                max-width: none;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <a class="btn" href="{{ ns()->url('dashboard/rencommissions/payment-history') }}">{{ __m('Back to Payment History', 'RenCommissions') }}</a>
        <button class="btn primary" type="button" onclick="window.print()">{{ __m('Print Pay Document', 'RenCommissions') }}</button>
    </div>

    <div class="sheet">
        <div class="header">
            <div>
                <h1>{{ __m('Commission Pay Document', 'RenCommissions') }}</h1>
                <p class="sub">{{ __m('Batch payout authorization sheet (not an individual payslip).', 'RenCommissions') }}</p>
            </div>
            <div class="meta">
                <div><strong>{{ __m('Reference', 'RenCommissions') }}:</strong> {{ $payout->reference }}</div>
                <div><strong>{{ __m('Generated', 'RenCommissions') }}:</strong> {{ $generatedAt->format('Y-m-d H:i') }}</div>
                <div><strong>{{ __m('Period', 'RenCommissions') }}:</strong> {{ optional($payout->period_start)->format('Y-m-d') }} - {{ optional($payout->period_end)->format('Y-m-d') }}</div>
            </div>
        </div>

        <div class="summary">
            <div class="card">
                <div class="k">{{ __m('Payable Date', 'RenCommissions') }}</div>
                <div class="v"><div class="date-line"></div></div>
            </div>
            <div class="card">
                <div class="k">{{ __m('Total Earners', 'RenCommissions') }}</div>
                <div class="v">{{ $rows->count() }}</div>
            </div>
            <div class="card">
                <div class="k">{{ __m('Commission Entries', 'RenCommissions') }}</div>
                <div class="v">{{ (int) $payout->entries_count }}</div>
            </div>
            <div class="card">
                <div class="k">{{ __m('Payout Total', 'RenCommissions') }}</div>
                <div class="v">{{ ns()->currency->define((float) $payout->total_amount)->format() }}</div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 36px;">#</th>
                    <th>{{ __m('Earner', 'RenCommissions') }}</th>
                    <th style="width: 200px;" class="amount">{{ __m('Payable', 'RenCommissions') }}</th>
                    <th style="width: 130px;">{{ __m('Date', 'RenCommissions') }}</th>
                    <th style="width: 220px;">{{ __m('Signature', 'RenCommissions') }}</th>
                </tr>
            </thead>
            <tbody>
            @forelse($rows as $index => $row)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        {{ $row->earner_name }}
                        @if(! empty($row->earner_identifier) && $row->earner_identifier !== $row->earner_name)
                            <div class="earner-meta">{{ $row->earner_identifier }}</div>
                        @endif
                    </td>
                    <td class="amount">{{ ns()->currency->define((float) $row->total_amount)->format() }}</td>
                    <td><div class="date-line"></div></td>
                    <td><div class="signature"></div></td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">{{ __m('No payout rows found for this reference.', 'RenCommissions') }}</td>
                </tr>
            @endforelse
            </tbody>
        </table>

        <div class="notes">
            <strong>{{ __m('Notes', 'RenCommissions') }}:</strong>
            {{ $payout->notes ?: __m('No additional notes.', 'RenCommissions') }}
        </div>

        <div class="footer-lines">
            <div class="line">{{ __m('Prepared By', 'RenCommissions') }}</div>
            <div class="line">{{ __m('Verified By', 'RenCommissions') }}</div>
            <div class="line">{{ __m('Approved By', 'RenCommissions') }}</div>
        </div>
    </div>
</body>
</html>
