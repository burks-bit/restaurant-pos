@php
    $copies = [
        ['show_acknowledgement' => false],
        ['show_acknowledgement' => true],
    ];

    $basicPay      = (float) ($item->basic_pay ?? 0); 
    $earningsRows    = $item->earnings->sum(fn($e) => (float) $e->amount);
    $computedTotal   = $basicPay + $earningsRows;
    $totalDeductions = (float) ($item->total_deductions ?? 0);
    $netPay          = $computedTotal - $totalDeductions;
@endphp

@foreach($copies as $copy)
<div style="font-family: Calibri, 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 12px; width: 100%; max-width: 100%; border: 1px solid #ccc; padding: 15px; box-sizing: border-box; {{ !$loop->last ? 'border-bottom: 2px dashed #aaa; padding-bottom: 15px; margin-bottom: 10px;' : '' }}">

    <!-- HEADER -->
    <table width="100%" style="border-collapse: collapse; margin-bottom: 10px;">
        <tr>
            <td style="border: none;">
                <div style="font-size: 14px; font-weight: bold;">
                    {{ $branch->name ?? 'Branch Name' }}
                </div>
                <div style="font-size: 12px; color: #666;">
                    {{ $branch->address ?? '' }}
                </div>
                <div style="font-size: 12px; color: #666;">
                    0906-088-9602
                </div>
            </td>
            <td style="border: none; text-align: right;">
                <img src="{{ public_path('storage/web_images/hsb1v2.jpg') }}" alt="Logo" style="height: 60px;">
            </td>
        </tr>
    </table>

    <!-- EMPLOYEE INFO -->
    <table width="100%" cellpadding="3" cellspacing="0" style="border-collapse: collapse; margin-bottom: 10px;">
        <tr>
            <td style="border: none; font-size: 12px;" valign="top">
                <strong>Employee:</strong>
                {{ $item->employee->first_name }} {{ $item->employee->last_name }}<br>
                <strong>Employee No.:</strong>
                {{ $item->employee->employee_code }}
            </td>
            <td style="border: none; text-align: center; font-size: 12px;" valign="top">
                <strong>Position:</strong>
                {{ $roles[$item->employee->access] ?? 'Unknown Role' }}
            </td>
            <td style="border: none; text-align: right; font-size: 12px;" valign="top">
                <strong>Payslip Period</strong><br>
                {{ $cutoff }}
            </td>
        </tr>
        <tr>
            <td style="border: none; font-size: 12px;" valign="top" colspan="2">
                <strong>No. of Days:</strong>
                {{ $item->days }}
            </td>
            <td style="border: none; text-align: right; font-size: 12px;" valign="top">
                <strong>Rate per Day:</strong>
                ₱ {{ number_format((float)($item->employee->latestEmployment?->daily_rate ?? 0), 2) }}
            </td>
        </tr>
    </table>

    <!-- EARNINGS & DEDUCTIONS -->
    <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse; margin-bottom: 8px;">
        <tr>

            <!-- LEFT: EARNINGS -->
            <td width="50%" valign="top" style="padding-right: 5px;">
                <table width="100%" cellpadding="4" cellspacing="0" style="border-collapse: collapse;">
                    <tr style="background: #f2f2f2;">
                        <th style="border: 1px solid #ccc; text-align: left; font-size: 12px;">Earnings</th>
                        <th style="border: 1px solid #ccc; text-align: right; font-size: 12px;">Amount</th>
                    </tr>

                    <!-- Basic Pay always first -->
                    <tr>
                        <td style="border: 1px solid #ccc; font-size: 12px;">Basic Pay</td>
                        <td style="border: 1px solid #ccc; text-align: right; font-size: 12px;">
                            ₱ {{ number_format($item->basic_pay, 2) }}
                        </td>
                    </tr>

                    <!-- Additional earnings rows -->
                    @forelse($item->earnings as $e)
                    <tr>
                        <td style="border: 1px solid #ccc; font-size: 12px;">
                            {{ ucwords(str_replace('_', ' ', $e->earning_type)) }}
                        </td>
                        <td style="border: 1px solid #ccc; text-align: right; font-size: 12px;">
                            ₱ {{ number_format((float)$e->amount, 2) }}
                        </td>
                    </tr>
                    @empty
                    @endforelse

                    <!-- Total Earnings = Basic Pay + all earning rows -->
                    <tr style="background: #dff0d8;">
                        <td style="border: 1px solid #bbb; font-size: 12px; font-weight: bold;">
                            Total Earnings
                        </td>
                        <td style="border: 1px solid #bbb; text-align: right; font-size: 12px; font-weight: bold;">
                            ₱ {{ number_format($computedTotal, 2) }}
                        </td>
                    </tr>
                </table>
            </td>

            <!-- RIGHT: DEDUCTIONS -->
            <td width="50%" valign="top" style="padding-left: 5px;">
                <table width="100%" cellpadding="4" cellspacing="0" style="border-collapse: collapse;">
                    <tr style="background: #f2f2f2;">
                        <th style="border: 1px solid #ccc; text-align: left; font-size: 12px;">Deductions</th>
                        <th style="border: 1px solid #ccc; text-align: right; font-size: 12px;">Amount</th>
                    </tr>

                    @forelse($item->deductions as $d)
                    <tr>
                        <td style="border: 1px solid #ccc; font-size: 12px;">
                            {{ ucwords(str_replace('_', ' ', $d->deduction_type)) }}
                        </td>
                        <td style="border: 1px solid #ccc; text-align: right; font-size: 12px;">
                            ₱ {{ number_format((float)$d->amount, 2) }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="2" style="border: 1px solid #ccc; text-align: center; font-size: 12px;">
                            No deductions
                        </td>
                    </tr>
                    @endforelse

                    <tr style="background: #f2dede;">
                        <td style="border: 1px solid #bbb; font-size: 12px; font-weight: bold;">
                            Total Deductions
                        </td>
                        <td style="border: 1px solid #bbb; text-align: right; font-size: 12px; font-weight: bold;">
                            ₱ {{ number_format($totalDeductions, 2) }}
                        </td>
                    </tr>
                </table>
            </td>

        </tr>
    </table>

    <!-- NET PAY = Total Earnings (incl. Basic Pay) - Total Deductions -->
    <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse; margin-top: 8px;">
        <tr>
            <td>
                <table width="100%" cellpadding="5" cellspacing="0" style="border-collapse: collapse;">
                    <tr style="background: #d6eaf8;">
                        <td style="border: 2px solid #2980b9; font-size: 13px; font-weight: bold; padding: 6px 10px;">
                            NET PAY
                        </td>
                        <td style="border: 2px solid #2980b9; font-size: 13px; font-weight: bold; text-align: right; padding: 6px 10px;">
                            ₱ {{ number_format($netPay, 2) }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- ACKNOWLEDGEMENT (second copy only) -->
    @if($copy['show_acknowledgement'])
    <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse; margin-top: 24px;">
        <tr>
            <td width="55%" style="border: none; font-size: 12px; padding-right: 10px;">
                <div style="margin-bottom: 4px; font-size: 11px; color: #555;">Received by:</div>
                <div style="border-bottom: 1px solid #333; min-width: 200px; height: 22px; margin-bottom: 4px;">
                    &nbsp;
                </div>
                <div style="font-size: 11px; text-align: center; color: #333;">
                    {{ $item->employee->first_name }} {{ $item->employee->last_name }}
                </div>
            </td>
            <td width="45%" style="border: none; font-size: 11px; color: #444; text-align: left; vertical-align: bottom; padding-left: 10px; padding-bottom: 2px; font-style: italic;">
                "I hereby acknowledge the receipt of the amount stated above."
            </td>
        </tr>
    </table>
    @endif

</div>
@endforeach