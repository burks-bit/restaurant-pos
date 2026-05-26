<div style="font-family: sans-serif; font-size: 11px; width: 100%; max-width: 100%; border:1px solid #ccc; padding:15px; height:48%;">

    <!-- HEADER -->
    <table width="100%" style="border-collapse: collapse; margin-bottom:10px;">
        <tr>
            <td style="border:none;">
                <div style="font-size:16px; font-weight:bold;">
                    {{ $branch->name ?? 'Branch Name' }}
                </div>
                <div style="font-size:11px; color:#666;">
                    {{ $branch->address ?? '' }}
                </div>
            </td>

            <td style="border:none; text-align:right;">
                <div style="font-size:18px; font-weight:bold;">PAYSLIP</div>
                <div style="font-size:11px;">
                    Cutoff: {{ $payroll->cutoff_start }} - {{ $payroll->cutoff_end }}
                </div>
            </td>
        </tr>
    </table>

    <!-- EMPLOYEE INFO -->
    <table width="100%" cellpadding="4" cellspacing="0" style="border-collapse: collapse; margin-bottom:10px;">
        <tr>
            <td style="border:none;">
                <strong>Employee:</strong>
                {{ $item->employee->first_name }} {{ $item->employee->last_name }} <br>

                <strong>Employee Code:</strong>
                {{ $item->employee->employee_code }}
            </td>

            <td style="border:none; text-align:right;">
                <strong>Payslip #:</strong> {{ $item->id }} <br>
                <strong>Date:</strong> {{ now()->format('Y-m-d') }}
            </td>
        </tr>
    </table>

    <!-- EARNINGS -->
    <table width="100%" cellpadding="5" cellspacing="0" style="border-collapse: collapse; margin-bottom:10px;">
        <tr style="background:#f2f2f2;">
            <th style="border:1px solid #ccc; text-align:left;">Earnings</th>
            <th style="border:1px solid #ccc; text-align:right;">Amount</th>
        </tr>

        <tr>
            <td style="border:1px solid #ccc;">Basic Pay</td>
            <td style="border:1px solid #ccc; text-align:right;">
                ₱ {{ number_format((float)$item->basic_pay, 2) }}
            </td>
        </tr>

        <tr>
            <td style="border:1px solid #ccc;">Overtime Pay</td>
            <td style="border:1px solid #ccc; text-align:right;">
                ₱ {{ number_format((float)$item->overtime_pay, 2) }}
            </td>
        </tr>

        <tr>
            <td style="border:1px solid #ccc;">Allowances</td>
            <td style="border:1px solid #ccc; text-align:right;">
                ₱ {{ number_format((float)$item->allowances, 2) }}
            </td>
        </tr>

        <tr style="font-weight:bold;">
            <td style="border:1px solid #ccc;">Gross Pay</td>
            <td style="border:1px solid #ccc; text-align:right;">
                ₱ {{ number_format((float)$item->gross_pay, 2) }}
            </td>
        </tr>
    </table>

    <!-- DEDUCTIONS -->
    <table width="100%" cellpadding="5" cellspacing="0" style="border-collapse: collapse;">
        <tr style="background:#f2f2f2;">
            <th style="border:1px solid #ccc; text-align:left;">Deductions</th>
            <th style="border:1px solid #ccc; text-align:right;">Amount</th>
        </tr>

        @forelse($item->deductions as $d)
        <tr>
            <td style="border:1px solid #ccc; text-transform:capitalize;">
                {{ $d->deduction_type }}
            </td>
            <td style="border:1px solid #ccc; text-align:right;">
                ₱ {{ number_format((float)$d->amount, 2) }}
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="2" style="border:1px solid #ccc; text-align:center;">
                No deductions
            </td>
        </tr>
        @endforelse

        <tr style="font-weight:bold;">
            <td style="border:1px solid #ccc;">Total Deductions</td>
            <td style="border:1px solid #ccc; text-align:right;">
                ₱ {{ number_format((float)$item->total_deductions, 2) }}
            </td>
        </tr>

        <tr style="font-weight:bold; background:#f9f9f9;">
            <td style="border:1px solid #ccc;">Net Pay</td>
            <td style="border:1px solid #ccc; text-align:right;">
                ₱ {{ number_format((float)$item->net_pay, 2) }}
            </td>
        </tr>
    </table>

</div>