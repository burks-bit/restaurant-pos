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
        h3 {
            margin-bottom: 6px;
            font-size: 13px;
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
        .sold-drinks {
            margin-bottom: 24px;
        }
        .sold-drinks table th {
            background: #e8f5e9;
        }
        .sold-drinks tfoot td {
            background: #f2f2f2;
            font-weight: bold;
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
        <p><strong>Reservation Fee:</strong> ₱{{ number_format($totalReservationFee, 2) }}</p>
        <p><strong>Total Expenses:</strong> ₱{{ number_format($totalExpenses, 2) }}</p>
        <p><strong>Net Sales (After Expenses):</strong> ₱{{ number_format($netSales, 2) }}</p>
        <p><strong>Voided Sales:</strong> ₱{{ number_format($voidedSales, 2) }}</p>
    </div>

    {{-- ── Sold Drinks ─────────────────────────────────────────────────── --}}
    @if(!empty($consumedAddons) && count($consumedAddons) > 0)
    <div class="sold-drinks">
        <h3>Sold Drinks</h3>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Item Name</th>
                    <th class="right">Qty</th>
                    <th class="right">Unit Price</th>
                    <th class="right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($consumedAddons as $i => $addon)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $addon['item_name'] ?? $addon->item_name }}</td>
                    <td class="right">{{ $addon['quantity'] ?? $addon->quantity }}</td>
                    <td class="right">₱{{ number_format($addon['unit_price'] ?? $addon->unit_price, 2) }}</td>
                    <td class="right">₱{{ number_format($addon['total'] ?? $addon->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="right">Total Drinks Sales:</td>
                    <td class="right">
                        ₱{{ number_format(collect($consumedAddons)->sum(fn($a) => is_array($a) ? $a['total'] : $a->total), 2) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif
    {{-- ─────────────────────────────────────────────────────────────────── --}}

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

                    <td class="right">₱{{ number_format($gross, 2) }}</td>
                    <td class="right">₱{{ number_format($gcashAmount, 2) }}</td>
                    <td class="right">₱{{ number_format($cashAmount, 2) }}</td>
                    <td class="right">₱{{ number_format($gross, 2) }}</td>
                    <td class="right">₱{{ number_format($discount, 2) }}</td>
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