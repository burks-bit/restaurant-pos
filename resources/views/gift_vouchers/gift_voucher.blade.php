<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Voucher {{ $voucher->control_no }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            color: #333;
        }

        .voucher-box {
            border: 2px dashed #333;
            padding: 30px;
            width: 100%;
        }

        .branch-header {
            text-align: center;
            margin-bottom: 20px;
        }

        .branch-name {
            font-size: 22px;
            font-weight: bold;
        }

        .branch-details {
            font-size: 13px;
            margin-top: 5px;
        }

        .voucher-title {
            font-size: 26px;
            font-weight: bold;
            margin: 25px 0 10px;
            text-align: center;
        }

        .voucher-type {
            font-size: 20px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 15px;
        }

        .info-row {
            font-size: 16px;
            margin: 8px 0;
            text-align: center;
        }

        .footer {
            font-size: 12px;
            margin-top: 30px;
            text-align: center;
        }

        .divider {
            border-top: 1px solid #999;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="voucher-box">

        <!-- Branch Details -->
        <div class="branch-header">
            <div class="branch-name">{{ $branch->name }}</div>
            <div class="branch-details">
                {{ $branch->address }} <br>
                Contact: {{ $branch->contact }}
            </div>
        </div>

        <div class="divider"></div>

        <!-- Voucher Details -->
        <div class="voucher-title">GIFT VOUCHER</div>

        <div class="voucher-type">{{ $voucher->type }}</div>

        <div class="info-row">
            <strong>Control No:</strong> {{ $voucher->control_no }}
        </div>

        <div class="info-row">
            <strong>Valid Until:</strong>
            {{ \Carbon\Carbon::parse($voucher->validity)->format('M. d, Y') }}
        </div>

        <div class="footer">
            Valid for one use only.<br>
            Presented voucher must be surrendered to cashier upon redemption.
        </div>

    </div>
</body>
</html>