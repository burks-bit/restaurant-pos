<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sales Report</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
        }
        h2 {
            text-align: center;
            margin-bottom: 5px;
        }
        .filters {
            text-align: center;
            margin-bottom: 12px;
            line-height: 1.6;
        }
        .summary {
            margin-bottom: 20px;
        }
        .summary p {
            margin: 3px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid #000;
        }
        th {
            background: #f2f2f2;
            padding: 6px;
        }
        td {
            padding: 5px;
        }
        .right {
            text-align: right;
        }
        .text-danger {
            color: red;
        }
        .single-order {
            background: #fff8db;
        }
        .reservation-order {
            background: #eff6ff;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: 10px;
            border-radius: 3px;
        }
        .badge-single {
            border: 1px solid #d4b106;
            background: #fff3b0;
            color: #7a5d00;
        }
        .badge-reservation {
            border: 1px solid #93c5fd;
            background: #dbeafe;
            color: #1d4ed8;
        }
    </style>
</head>
<body>

    <h2>SALES REPORT</h2>

    @php
        $selectedStatusLabel = !empty($status) ? ucfirst($status) : 'All';
    @endphp

    <div class="filters">
        @if($startDate && $endDate)
            <div>
                <strong>From:</strong> {{ $startDate }}
                &nbsp;&nbsp;
                <strong>To:</strong> {{ $endDate }}
            </div>
        @endif

        <div>
           <strong>Cashier:</strong> {{ $cashierName }}
            &nbsp;&nbsp;
            <strong>Status:</strong> {{ $selectedStatusLabel }}
        </div>
    </div>

    <div class="summary">
        <p><strong>Gross Sales:</strong> ₱{{ number_format($grossSales, 2) }}</p>
        <p><strong>Cash Sales:</strong> ₱{{ number_format($totalCashSales, 2) }}</p>
        <p><strong>GCash Sales:</strong> ₱{{ number_format($totalGcashSales, 2) }}</p>
        <p><strong>Total Discounts:</strong> ₱{{ number_format($totalDiscount, 2) }}</p>
        <p><strong>Total Expenses:</strong> ₱{{ number_format($totalExpenses, 2) }}</p>
        <p><strong>Net Sales (After Expenses):</strong> ₱{{ number_format($netSales, 2) }}</p>
        <p><strong>Voided Sales:</strong> ₱{{ number_format($voidedSales, 2) }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>Cashier</th>
                <th>Table #</th>
                <th>Customer Name</th>
                <th>No. of Pax</th>
                <th class="right">Amount</th>
                <th class="right">Gcash Payment Amount</th>
                <th class="right">Cash Payment Amount</th>
                <th class="right">Total Gross Sales</th>
                <th class="right">Less Discount/Voucher</th>
                <th class="right">Total Net Sales</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $index => $order)

                @php
                    $isReservationOrder = str_starts_with($order->order_no ?? '', 'RSVP');
                    $isSingleOrder      = !$isReservationOrder
                                            && empty($order->table_session)
                                            && empty($order->table_number);

                    $payments = collect($order->payments ?? [])->where('is_void', 0);

                    $gross    = (float) $order->subtotal;
                    $discount = (float) ($order->total_discount ?? 0);
                    $net      = (float) $order->total;

                    $gcashAmount = $payments
                        ->where('payment_method_id', 2)
                        ->sum(fn($p) => (float) ($p->amount ?? 0));

                    $cashAmount = $payments
                        ->where('payment_method_id', 1)
                        ->sum(fn($p) => (float) ($p->amount ?? 0));

                    $rowClass = $isReservationOrder ? 'reservation-order'
                              : ($isSingleOrder     ? 'single-order'
                              : '');
                @endphp

                <tr class="{{ $rowClass }}">
                    <td>{{ $index + 1 }}</td>

                    <td>{{ \Carbon\Carbon::parse($order->created_at)->format('m/d/Y') }}</td>

                    <td>{{ $order->user->name ?? 'N/A' }}</td>

                    {{-- Table / Type label spanning 3 columns --}}
                    @if($isReservationOrder)
                        <td colspan="3">
                            <span class="badge badge-reservation">Reservation Fee</span>
                            &nbsp;{{ $order->order_no }}
                        </td>
                    @elseif($isSingleOrder)
                        <td colspan="3">
                            <span class="badge badge-single">Single Order</span>
                        </td>
                    @else
                        <td>{{ $order->tableSession->table->name ?? $order->table_number ?? '—' }}</td>
                        <td>{{ $order->tableSession->customer_name ?? '-' }}</td>
                        <td>{{ $order->tableSession->pax ?? '-' }}</td>
                    @endif

                    <!-- Amount (Subtotal / Gross) -->
                    <td class="right">₱{{ number_format($gross, 2) }}</td>

                    <!-- GCash -->
                    <td class="right">₱{{ number_format($gcashAmount, 2) }}</td>

                    <!-- Cash -->
                    <td class="right">₱{{ number_format($cashAmount, 2) }}</td>

                    <!-- Total Gross Sales -->
                    <td class="right">₱{{ number_format($gross, 2) }}</td>

                    <!-- Discount -->
                    <td class="right">₱{{ number_format($discount, 2) }}</td>

                    <!-- Net -->
                    <td class="right {{ $order->status === 'cancelled' ? 'text-danger' : '' }}">
                        ₱{{ number_format($net, 2) }}
                    </td>
                </tr>

            @empty
                <tr>
                    <td colspan="12" style="text-align:center;">No records found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>