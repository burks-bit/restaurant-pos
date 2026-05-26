<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt #{{ $order->order_no }}</title>
    <style>
        body {
            font-family: DejaVu Sans, monospace, sans-serif;
            font-size: 10px;
            width: 89%;
            margin: 0;
            color: #000;
        }

        .receipt {
            width: 89%;
        }

        .center {
            text-align: center;
        }

        .header {
            margin-bottom: 8px;
        }

        .branch-name {
            font-size: 12px;
            font-weight: bold;
            margin: 0 0 4px 0;
        }

        .muted {
            font-size: 10px;
            color: #333;
        }

        .section {
            margin-top: 8px;
            margin-bottom: 8px;
        }

        .divider {
            border-top: 1px dashed #000;
            margin: 6px 0;
        }

        .info-table,
        .line-table,
        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 1px 0;
            vertical-align: top;
        }

        .line-table th,
        .line-table td {
            padding: 3px 0;
            vertical-align: top;
        }

        .line-table th {
            font-size: 10px;
            text-align: left;
            border-bottom: 1px dashed #000;
        }

        .line-table .qty,
        .line-table .amt {
            text-align: right;
            white-space: nowrap;
        }

        .section-title {
            font-weight: bold;
            font-size: 10px;
            margin: 4px 0;
        }

        .totals-table td {
            padding: 2px 0;
        }

        .totals-table .label {
            text-align: left;
        }

        .totals-table .value {
            text-align: right;
            white-space: nowrap;
        }

        .grand-total td {
            font-weight: bold;
            font-size: 10px;
            border-top: 1px dashed #000;
            padding-top: 5px;
        }

        .footer {
            text-align: center;
            margin-top: 8px;
            font-size: 10px;
        }

        .small {
            font-size: 10px;
        }

        .cut-line {
            border-top: 1px dashed #000;
            margin: 12px 0;
            text-align: center;
            position: relative;
        }
        .cut-line::before {
            content: '✂ - - - - - - - - - - - - - - - - - - - - - - ✂';
            font-size: 9px;
            background: #fff;
            padding: 0 4px;
            position: absolute;
            top: -7px;
            left: 50%;
            transform: translateX(-50%);
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <div class="receipt">

        {{-- HEADER --}}
        <div class="header center">
            <div class="branch-name">{{ $branch->name ?? 'Branch' }}</div>
            <div class="muted">{{ $branch->address ?? '' }}</div>
        </div>

        <div class="divider"></div>

        {{-- ORDER INFO --}}
        <table class="info-table section">
            <tr>
                <td><strong>Order #:</strong></td>
                <td style="text-align:right;">{{ $order->order_no }}</td>
            </tr>
            <tr>
                <td><strong>Date:</strong></td>
                <td style="text-align:right;">{{ \Carbon\Carbon::parse($order->created_at)->format('Y-m-d h:i A') }}</td>
            </tr>
            <tr>
                <td><strong>Cashier:</strong></td>
                <td style="text-align:right;">{{ $order->user->name ?? 'N/A' }}</td>
            </tr>

            @if(!empty($order->tableSession))
                <tr>
                    <td><strong>Table:</strong></td>
                    <td style="text-align:right;">{{ $order->tableSession->table->name ?? ($order->table_number ?? 'N/A') }}</td>
                </tr>
                <tr>
                    <td><strong>Ref No:</strong></td>
                    <td style="text-align:right;">{{ $order->tableSession->ref_no ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td><strong>Customer:</strong></td>
                    <td style="text-align:right;">{{ $order->tableSession->customer_name ?? 'Walk-in' }}</td>
                </tr>
            @endif
        </table>

        <div class="divider"></div>

        {{-- BASE DINING CHARGES --}}
        <div class="section">
            <div class="section-title">BASE DINING CHARGES</div>
            <table class="line-table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th class="qty">Qty</th>
                        <th class="amt">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($order->orderHeads as $head)
                        <tr>
                            <td>
                                {{ $head->headPricingRule->label ?? ('Head Rule #' . $head->head_pricing_rule_id) }}
                                <div class="small">₱{{ number_format((float) $head->price_snapshot, 2) }} each</div>
                            </td>
                            <td class="qty">{{ number_format((float) $head->quantity, 0) }}</td>
                            <td class="amt">₱{{ number_format((float) $head->subtotal, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="center small">No base dining charges found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="divider"></div>

        {{-- ADD-ONS --}}
        <div class="section">
            <div class="section-title">ADD-ONS / EXTRA ORDERS</div>
            <table class="line-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th class="qty">Qty</th>
                        <th class="amt">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($order->addons as $addon)
                        <tr>
                            <td>
                                {{ $addon->item_name }}
                                <div class="small">
                                    {{ $addon->unit ?? 'N/A' }} • ₱{{ number_format((float) $addon->unit_price, 2) }}
                                </div>
                            </td>
                            <td class="qty">{{ number_format((float) $addon->quantity, 2) }}</td>
                            <td class="amt">₱{{ number_format((float) $addon->subtotal, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="center small">No add-ons posted.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($order->leftover)
            <div class="divider"></div>

            {{-- LEFTOVER CHARGE --}}
            <div class="section">
                <div class="section-title">LEFTOVER CHARGE</div>
                <table class="info-table">
                    <tr>
                        <td>Weight</td>
                        <td style="text-align:right;">{{ number_format((float) $order->leftover->weight, 2) }} g</td>
                    </tr>
                    <tr>
                        <td>Charge Amount</td>
                        <td style="text-align:right;">₱{{ number_format((float) $order->leftover->amount, 2) }}</td>
                    </tr>
                </table>
            </div>
        @endif

        <div class="divider"></div>

        {{-- TOTALS --}}
        <div class="section">
            <table class="totals-table">
                <tr>
                    <td class="label">Base Dining</td>
                    <td class="value">₱{{ number_format($headsSubtotal, 2) }}</td>
                </tr>
                <tr>
                    <td class="label">Add-ons</td>
                    <td class="value">₱{{ number_format($addonsSubtotal, 2) }}</td>
                </tr>

                @if($leftoverAmount > 0)
                    <tr>
                        <td class="label">Leftover Charge</td>
                        <td class="value">₱{{ number_format($leftoverAmount, 2) }}</td>
                    </tr>
                @endif

                @if($discount > 0)
                    <tr>
                        <td class="label">
                            Discount
                            @if($order->voucher_no_used)
                                (Voucher: {{ $order->voucher_no_used }})
                            @elseif($order->discount_type)
                                ({{ $order->discount_type }})
                            @endif
                        </td>
                        <td class="value">-₱{{ number_format($discount, 2) }}</td>
                    </tr>
                @endif

                <tr class="grand-total">
                    <td class="label">TOTAL</td>
                    <td class="value">₱{{ number_format($computedTotal, 2) }}</td>
                </tr>
            </table>
        </div>

        <div class="divider"></div>

        {{-- PAYMENT BREAKDOWN --}}
        <div class="section">
            <div class="section-title">PAYMENT DETAILS</div>

            <table class="line-table">
                <thead>
                    <tr>
                        <th>Method</th>
                        <th class="amt">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $validPayments = $order->payments->where('is_void', false);
                        $totalPaid = $validPayments->sum(function ($payment) {
                            return (float) $payment->amount;
                        });
                    @endphp

                    @forelse($validPayments as $payment)
                        <tr>
                            <td>
                                {{ $payment->paymentMethod->name ?? 'Unknown' }}

                                @if(!empty($payment->reference_no))
                                    <div class="small">Ref: {{ $payment->reference_no }}</div>
                                @endif

                                @if(!empty($payment->remarks))
                                    <div class="small">{{ $payment->remarks }}</div>
                                @endif
                            </td>
                            <td class="amt">₱{{ number_format((float) $payment->amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="center small">No payment records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <table class="totals-table" style="margin-top: 6px;">
                <tr>
                    <td class="label">Total Paid</td>
                    <td class="value">₱{{ number_format((float) $totalPaid, 2) }}</td>
                </tr>
                <tr>
                    <td class="label">Change</td>
                    <td class="value">₱{{ number_format((float) ($order->change_amount ?? 0), 2) }}</td>
                </tr>
            </table>
        </div>

        <br>
        <br>
        <div class="cut-line"></div>
        <br>
        <br>

        <!-- GATE PASS -->
        <div class="header center">
            <div class="branch-name">GATE PASS</div>
            <div class="muted">{{ $branch->address ?? '' }}</div>
        </div>

        <div class="divider"></div>

        <table class="info-table section">
            <tr>
                <td><strong>Order #:</strong></td>
                <td style="text-align:right;">{{ $order->order_no }}</td>
            </tr>
            <tr>
                <td><strong>Date:</strong></td>
                <td style="text-align:right;">{{ \Carbon\Carbon::parse($order->created_at)->format('Y-m-d h:i A') }}</td>
            </tr>
            @if(!empty($order->tableSession))
                <tr>
                    <td><strong>Table:</strong></td>
                    <td style="text-align:right;">{{ $order->tableSession->table->name ?? ($order->table_number ?? 'N/A') }}</td>
                </tr>
                <tr>
                    <td><strong>No. of Pax:</strong></td>
                    <td style="text-align:right;">{{ $order->orderHeads->sum('quantity') }}</td>
                </tr>
            @endif
            <tr>
                <td><strong>TOTAL:</strong></td>
                <td style="text-align:right;"><strong>₱{{ number_format($computedTotal, 2) }}</strong></td>
            </tr>
        </table>

        <div class="divider"></div>

        {{-- FOOTER --}}
        <div class="footer">
            <div>Thank you for dining with us!</div>
        </div>

    </div>
</body>
</html>
