<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 6px; }
        th { background: #f3f3f3; }
        .total { font-weight: bold; }
        .header { text-align: center; margin-bottom: 15px; }
        .right { text-align: right; }
        .center { text-align: center; }
    </style>
</head>
<body>

<div class="header">
    <h2>Sales Report ({{ ucfirst($type) }})</h2>
    <p>{{ $start && $end ? "$start to $end" : 'Today' }}</p>
</div>

<table>
    <thead>
        <tr>
            <th width="5%">#</th>
            @if($type === 'daily')
                <th>Order No</th>
                <th>Date & Time</th>
                <th class="right">Subtotal (₱)</th>
                <th class="right">Discount (₱)</th>
                <th class="right">Total (₱)</th>
            @else
                <th>Date</th>
                <th class="right">Total Sales (₱)</th>
            @endif
        </tr>
    </thead>

    <tbody>
        @forelse ($sales as $i => $row)
            @if($type === 'daily')
                <tr>
                    <td class="center">{{ $i + 1 }}</td>
                    <td>{{ $row->order_no }}</td>
                    <td>{{ $row->created_at->format('Y-m-d h:i A') }}</td>
                    <td class="right">{{ number_format($row->subtotal, 2) }}</td>
                    <td class="right">{{ number_format($row->total_discount, 2) }}</td>
                    <td class="right">{{ number_format($row->total, 2) }}</td>
                </tr>
            @else
                <tr>
                    <td class="center">{{ $i + 1 }}</td>
                    <td>{{ $row['date'] }}</td>
                    <td class="right">{{ number_format($row['total'], 2) }}</td>
                </tr>
            @endif
        @empty
            <tr>
                <td colspan="{{ $type === 'daily' ? 6 : 2 }}" class="center">No records found</td>
            </tr>
        @endforelse

        <tr class="total">
            @if($type === 'daily')
                <td colspan="5">GRAND TOTAL</td>
            @else
                <td colspan="2">GRAND TOTAL</td>
            @endif
            <td class="right">₱{{ number_format($grandTotal, 2) }}</td>
        </tr>
    </tbody>
</table>

</body>
</html>
