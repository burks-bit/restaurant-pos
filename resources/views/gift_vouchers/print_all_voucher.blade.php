<!DOCTYPE html>
<html>
<head>
    <style>
    body { 
        font-family: Georgia, "Times New Roman", serif; 
        font-size: 10px;
        margin: 0;
        padding: 0;
        color: #1f3d2b;
    }

    table.voucher-row {
        width: 100%;
        border-collapse: collapse;
        margin: 0;
        padding: 0;
    }

    table.voucher-row tr {
        height: 98mm;
    }

    td.card-cell {
        width: 100%;
        height: 98mm;
        padding: 0;
        vertical-align: top;
    }

    table.card {
        width: 100%;
        height: 98mm;
        margin: 0;
        border: 2px solid #1f6f43;
        border-collapse: collapse;
        text-align: center;
        background: rgba(255,255,255,0.9);
    }

    .branch-header {
        border: 1px dashed #2f8f57;
        padding: 3mm;
        background: rgba(202, 244, 212, 0.7);
    }

    .branch-name { 
        font-weight: bold; 
        font-size: 26px;
        color: #171717;
        letter-spacing: 1px;
        text-transform: uppercase;
    }

    .branch-details { 
        font-size: 13px;
        color: #171717;
        font-style: italic;
        margin-top: 2px;
    }

    .voucher-title {
        padding-top: 20px; 
        font-weight: bold; 
        font-size: 17px;
        color: #171717;
        letter-spacing: 3px;
        text-transform: uppercase;
    }

    .voucher-type { 
        padding-top: 22px;
        font-size: 40px; 
        font-weight: bold;
        color: #065f31;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .control-no { 
        padding-top: 22px;
        font-weight: bold; 
        font-size: 22px;
        color: #171717;
    }

    .validity { 
        padding-top: 22px;
        padding-bottom: 24px;
        font-weight: bold; 
        font-size: 17px;
        color: #171717;
    }

    .branch-footer {
        border: 1px dashed #2f8f57;
        padding: 4mm;
        font-size: 13px;
        color: #171717;
        background: rgba(231,246,237,0.6);
        font-style: italic;
    }
</style>
</head>
<body>

@php
    $vouchersPerPage = 2;  // 2 columns x 4 rows
    $vouchersPerRow = 1;
@endphp

@foreach($vouchers->chunk($vouchersPerPage) as $pageChunk)
    @php $rows = $pageChunk->chunk($vouchersPerRow); @endphp

    @foreach($rows as $row)
        <table class="voucher-row">
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

            </tr>
        </table>
    @endforeach

    @if(!$loop->last)
        <pagebreak />
    @endif

@endforeach

</body>
</html>