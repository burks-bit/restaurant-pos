<!DOCTYPE html>
<html>
<head>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            font-size: 10px;
        }

        td.card-cell {
            width: 50%;
            height: 70mm; /* Bigger voucher height */
            padding: 3mm;
            vertical-align: middle;
        }

        table.card {
            width: 100%;
            height: 70mm;
            border: 2px solid #000000;
            border-collapse: collapse;
            text-align: center;
        }

        table.card td {
            padding: 2mm 3mm;
        }

        .branch-header {
            border: 1px dotted #000;
            padding: 3mm;
        }

        .branch-name { 
            font-weight: bold; 
            font-size: 16px; 
        }

        .branch-details { 
            font-size: 12px; 
        }

        .voucher-title { 
            font-weight: bold; 
            font-size: 13px; 
        }

        .voucher-type { 
            font-size: 22px; 
            font-weight: bold; 
        }

        .control-no { 
            font-weight: bold; 
            font-size: 13px;
        }

        .validity { 
            font-weight: bold; 
            font-size: 12px;
        }

        .branch-footer {
            border: 1px dotted #000;
            padding: 4mm;
            font-size: 10px;
        }
    </style>
</head>
<body>

@php
    $vouchersPerPage = 8;  // 2 columns x 4 rows
    $vouchersPerRow = 2;
@endphp

@foreach($vouchers->chunk($vouchersPerPage) as $pageChunk)
    @php $rows = $pageChunk->chunk($vouchersPerRow); @endphp

    @foreach($rows as $row)
        <table style="width:100%; border-collapse: collapse;">
            <tr>
                @foreach($row as $voucher)
                    <td class="card-cell">
                        <table class="card">
                            
                            <tr>
                                <td class="branch-header">
                                    <div class="branch-name">{{ $branch->name }}</div>
                                    <div class="branch-details">
                                        {{ $branch->address }}
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td class="voucher-title">--- GIFT VOUCHER ---</td>
                            </tr>

                            <tr>
                                <td class="voucher-type">{{ $voucher->type }}</td>
                            </tr>

                            <tr>
                                <td class="control-no">
                                    No: {{ $voucher->control_no }}
                                </td>
                            </tr>

                            <tr>
                                <td class="validity">
                                    Valid Until: {{ \Carbon\Carbon::parse($voucher->validity)->format('M d, Y') }}
                                </td>
                            </tr>

                            <tr>
                                <td class="branch-footer">
                                    <i>
                                        Thank you for choosing Hapag sa Balai! 
                                        Enjoy our Eat-All-You-Can experience and 
                                        make every meal a feast to remember.
                                    </i>
                                </td>
                            </tr>

                        </table>
                    </td>
                @endforeach

                @if($row->count() == 1)
                    <td class="card-cell"></td>
                @endif

            </tr>
        </table>
    @endforeach

    @if(!$loop->last)
        <pagebreak />
    @endif

@endforeach

</body>
</html>