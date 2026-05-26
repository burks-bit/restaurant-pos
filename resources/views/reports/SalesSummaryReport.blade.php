<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sales Summary Report</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; color: #222; margin: 20px; }

        h2 { text-align: center; font-size: 18px; margin: 0 0 4px 0; letter-spacing: 1px; text-transform: uppercase; }
        .subtitle { text-align: center; font-size: 11px; color: #666; margin-bottom: 4px; }
        .meta { text-align: center; font-size: 11px; margin-bottom: 10px; }

        hr { border: none; border-top: 2px solid #333; margin: 10px 0 16px 0; }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: #333;
            color: #fff;
            padding: 5px 10px;
            margin: 18px 0 6px 0;
        }

        .hint { font-size: 10px; color: #666; margin: 0 0 6px 0; }

        /* ── All tables share this base ── */
        table { width: 100%; border-collapse: collapse; margin-bottom: 0; font-size: 11px; }
        th {
            background: #4a4a4a;
            color: #fff;
            padding: 6px 8px;
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
        }
        td { padding: 5px 8px; border-bottom: 1px solid #e0e0e0; vertical-align: top; }
        tfoot td { background: #f2f2f2; font-weight: bold; border-top: 2px solid #333; border-bottom: none; }

        .right  { text-align: right; }
        .center { text-align: center; }

        .badge {
            padding: 2px 6px;
            font-size: 10px;
            font-weight: bold;
        }
        .badge-over     { background: #dcfce7; color: #15803d; border: 1px solid #86efac; }
        .badge-short    { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }
        .badge-balanced { background: #f3f4f6; color: #6b7280; border: 1px solid #d1d5db; }

        .comparison-row td { background: #fffbeb; font-weight: bold; border-top: 2px solid #f59e0b; }
    </style>
</head>
<body>

@php $selectedStatusLabel = !empty($status) ? ucfirst($status) : 'All'; @endphp

<!-- ══ HEADER ══ -->
<h2>Sales Summary Report</h2>
<div class="subtitle">End-of-Shift Financial Summary</div>
@if($startDate && $endDate)
    <div class="meta">
        <strong>Period:</strong> {{ $startDate }} &mdash; {{ $endDate }}
        &nbsp;&nbsp;|&nbsp;&nbsp;
        <strong>Cashier:</strong> {{ $cashierName }}
        &nbsp;&nbsp;|&nbsp;&nbsp;
        <strong>Status:</strong> {{ $selectedStatusLabel }}
    </div>
@endif
<hr>

<!-- ══ SECTION 1: SALES SUMMARY ══ -->
<div class="section-title">&#9312; Sales Summary</div>

<table style="margin-bottom:16px;">
    <tr>
        <td style="width:33%; border:1px solid #93c5fd; background:#eff6ff; padding:10px 12px;">
            <div style="font-size:10px; color:#888; text-transform:uppercase;">Gross Sales</div>
            <div style="font-size:16px; font-weight:bold; color:#1d4ed8;">&#8369;{{ number_format($grossSales, 2) }}</div>
        </td>
        <td style="width:33%; border:1px solid #93c5fd; background:#eff6ff; padding:10px 12px;">
            <div style="font-size:10px; color:#888; text-transform:uppercase;">Cash Sales</div>
            <div style="font-size:16px; font-weight:bold; color:#1d4ed8;">&#8369;{{ number_format($totalCashSales, 2) }}</div>
        </td>
        <td style="width:33%; border:1px solid #93c5fd; background:#eff6ff; padding:10px 12px;">
            <div style="font-size:10px; color:#888; text-transform:uppercase;">GCash Sales</div>
            <div style="font-size:16px; font-weight:bold; color:#1d4ed8;">&#8369;{{ number_format($totalGcashSales, 2) }}</div>
        </td>
    </tr>
    <tr>
        <td style="border:1px solid #fca5a5; background:#fff5f5; padding:10px 12px;">
            <div style="font-size:10px; color:#888; text-transform:uppercase;">Total Expenses</div>
            <div style="font-size:16px; font-weight:bold; color:#dc2626;">- &#8369;{{ number_format($totalExpenses, 2) }}</div>
        </td>
        <td colspan="2" style="border:1px solid #86efac; background:#f0fdf4; padding:10px 12px;">
            <div style="font-size:10px; color:#888; text-transform:uppercase;">Net Sales (After Expenses)</div>
            <div style="font-size:16px; font-weight:bold; color:#16a34a;">&#8369;{{ number_format($netSales, 2) }}</div>
        </td>
    </tr>
</table>

<!-- ══ SECTION 2: POSTED CASH REGISTERS ══ -->
@php
    $castedCashes    = collect($posted_cashes ?? []);
    $totalPostedCash = $castedCashes->sum(fn($c) => (float) ($c->cash_on_hand ?? 0));
    $totalPostedNet  = $castedCashes->sum(fn($c) => (float) ($c->net_sales ?? 0));
    $overShortTotal  = $totalPostedCash - (float) ($netSales ?? 0);
@endphp

<div class="section-title">&#9313; Posted Cash Registers</div>
<p class="hint">Shows each end-of-shift cash submission. Over/Short compares cash on hand vs posted net sales.</p>

<table>
    <thead>
        <tr>
            <th style="width:4%">#</th>
            <th style="width:13%">Date Posted</th>
            <th style="width:14%">Cashier</th>
            <th style="width:10%">Shift</th>
            <th style="width:12%; text-align:right;">Net Sales<br><span style="font-weight:normal;font-size:9px;">(posted)</span></th>
            <th style="width:12%; text-align:right;">Cash On Hand<br><span style="font-weight:normal;font-size:9px;">(counted)</span></th>
            <th style="width:11%; text-align:center;">Over / Short</th>
            <th>Denomination Breakdown</th>
        </tr>
    </thead>
    <tbody>
        @php $rowIndex = 0; @endphp
        @forelse($posted_cashes as $cash)
        @php
            $rowIndex++;
            $rawDenom = $cash->denomination ?? null;
            $denoms   = [];
            if (is_string($rawDenom) && !empty($rawDenom)) {
                $decoded = json_decode($rawDenom, true);
                $denoms  = is_array($decoded) ? array_filter($decoded, fn($d) => !is_null($d)) : [];
            } elseif (is_array($rawDenom)) {
                $denoms = array_filter($rawDenom, fn($d) => !is_null($d));
            }
            $diff       = (float) ($cash->cash_on_hand ?? 0) - (float) ($cash->net_sales ?? 0);
            $badgeClass = $diff > 0 ? 'badge-over' : ($diff < 0 ? 'badge-short' : 'badge-balanced');
            $diffLabel  = $diff > 0 ? 'OVER' : ($diff < 0 ? 'SHORT' : 'BALANCED');
            $rowBg      = $rowIndex % 2 === 0 ? '#f9f9f9' : '#ffffff';
        @endphp
            <tr style="background:{{ $rowBg }};">
                <td class="center">{{ $rowIndex }}</td>
                <td>
                    {{ \Carbon\Carbon::parse($cash->created_at)->format('m/d/Y') }}<br>
                    <span style="color:#888;font-size:10px;">{{ \Carbon\Carbon::parse($cash->created_at)->format('h:i A') }}</span>
                </td>
                <td>{{ optional($cash->cashier)->name ?? '&mdash;' }}</td>
                <td class="center">{{ optional($cash->shift)->name ?? '&mdash;' }}</td>
                <td class="right">&#8369;{{ number_format($cash->net_sales, 2) }}</td>
                <td class="right">&#8369;{{ number_format($cash->cash_on_hand, 2) }}</td>
                <td class="center">
                    <span class="badge {{ $badgeClass }}">{{ $diffLabel }}</span><br>
                    <span style="font-size:11px; font-weight:bold;">&#8369;{{ number_format(abs($diff), 2) }}</span>
                </td>
                <td>
                    @if(!empty($denoms))
                        @foreach($denoms as $d)
                            @if(!is_null($d) && isset($d['denom'], $d['qty']))
                                <div style="margin-bottom:2px; font-size:10px;">
                                    &#8369;{{ number_format($d['denom'], 0) }}
                                    &times; {{ $d['qty'] }}
                                    = <strong>&#8369;{{ number_format($d['denom'] * $d['qty'], 2) }}</strong>
                                </div>
                            @endif
                        @endforeach
                        <div style="border-top:1px solid #ccc; margin-top:3px; padding-top:3px; font-size:10px;">
                            <strong>Total: &#8369;{{ number_format($cash->cash_on_hand, 2) }}</strong>
                        </div>
                    @else
                        <span style="color:#aaa; font-size:10px; font-style:italic;">No denomination data</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="center" style="color:#aaa; padding:16px;">No posted cash registers found</td>
            </tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr>
            <td colspan="4"><strong>Subtotal (All Registers)</strong></td>
            <td class="right">&#8369;{{ number_format($totalPostedNet, 2) }}</td>
            <td class="right">&#8369;{{ number_format($totalPostedCash, 2) }}</td>
            <td class="center">
                <span class="badge {{ $overShortTotal > 0 ? 'badge-over' : ($overShortTotal < 0 ? 'badge-short' : 'badge-balanced') }}">
                    {{ $overShortTotal > 0 ? 'OVER' : ($overShortTotal < 0 ? 'SHORT' : 'BALANCED') }}
                </span><br>
                <span style="font-size:11px;">&#8369;{{ number_format(abs($overShortTotal), 2) }}</span>
            </td>
            <td></td>
        </tr>
        <tr class="comparison-row">
            <td colspan="4">&#9878; Comparison: Total Cash On Hand vs Net Sales (After Expenses)</td>
            <td class="right">&#8369;{{ number_format($netSales, 2) }}</td>
            <td class="right">&#8369;{{ number_format($totalPostedCash, 2) }}</td>
            <td class="center">
                <span class="badge {{ $overShortTotal > 0 ? 'badge-over' : ($overShortTotal < 0 ? 'badge-short' : 'badge-balanced') }}">
                    {{ $overShortTotal > 0 ? 'OVER' : ($overShortTotal < 0 ? 'SHORT' : 'BALANCED') }}
                </span><br>
                <span style="font-size:11px;">&#8369;{{ number_format(abs($overShortTotal), 2) }}</span>
            </td>
            <td></td>
        </tr>
    </tfoot>
</table>

<!-- ══ SECTION 3: EXPENSES BREAKDOWN ══ -->
<div class="section-title">&#9314; Expenses Breakdown</div>
<p class="hint">All recorded expenses deducted from gross sales to arrive at net sales.</p>

<table>
    <thead>
        <tr>
            <th style="width:4%">#</th>
            <th style="width:12%">Date</th>
            <th>Description / Purpose</th>
            <th style="width:15%">Posted By</th>
            <th style="width:10%">Shift</th>
            <th style="width:13%; text-align:right;">Amount</th>
        </tr>
    </thead>
    <tbody>
        @php $expIndex = 0; @endphp
        @forelse($expenses as $expense)
        @php
            $expIndex++;
            $expBg = $expIndex % 2 === 0 ? '#f9f9f9' : '#ffffff';
        @endphp
            <tr style="background:{{ $expBg }};">
                <td class="center">{{ $expIndex }}</td>
                <td>{{ \Carbon\Carbon::parse($expense->expense_date)->format('m/d/Y') }}</td>
                <td>{{ $expense->description }}</td>
                <td>{{ optional($expense->creator)->name ?? '&mdash;' }}</td>
                <td class="center">{{ optional($expense->shift)->name ?? '&mdash;' }}</td>
                <td class="right" style="color:#dc2626;">&#8369;{{ number_format($expense->amount, 2) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="center" style="color:#aaa; padding:16px;">No expenses recorded</td>
            </tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr>
            <td colspan="5"><strong>Total Expenses</strong></td>
            <td class="right" style="color:#dc2626;">&#8369;{{ number_format($totalExpenses, 2) }}</td>
        </tr>
    </tfoot>
</table>

</body>
</html>