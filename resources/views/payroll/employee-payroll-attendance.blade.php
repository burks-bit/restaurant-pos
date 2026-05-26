<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payroll Attendance - {{ $employee->employee_code }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.4;
        }

        h1, h2, h3 {
            margin: 0;
            padding: 0;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .employee-info {
            margin-bottom: 10px;
        }

        .employee-info span {
            display: inline-block;
            width: 200px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        table th, table td {
            border: 1px solid #ccc;
            padding: 6px 8px;
            text-align: center;
        }

        table th {
            background-color: #f2f2f2;
            text-transform: uppercase;
            font-size: 11px;
        }

        tr.day-off td {
            background-color: #e5e7eb; /* Gray for Day Off */
        }

        tr.absent td {
            background-color: #fee2e2; /* Red for Absent */
        }

        tr.perfect td {
            background-color: #dcfce7; /* Green for Perfect Attendance */
        }

        .totals {
            font-weight: bold;
            text-align: right;
            margin-top: 10px;
        }

        .footer {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
        }

        .footer div {
            text-align: center;
        }
    </style>
</head>
<body>

<div class="header">
    <h2>Employee Attendance Payroll</h2>
    <h3>{{ $employee->first_name }} {{ $employee->last_name }} ({{ $employee->employee_code }})</h3>
    @if($startDate && $endDate)
        <p>Period: {{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}</p>
    @endif
</div>

<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Shift</th>
            <th>Scheduled In</th>
            <th>Scheduled Out</th>
            <th>Actual In</th>
            <th>Actual Out</th>
            <th>Late</th>
            <th>Undertime</th>
        </tr>
    </thead>
    <tbody>
        @foreach($schedules as $sch)
            @php
                $rowClass = '';
                if(in_array($sch->status, ['Day Off'])) $rowClass = 'day-off';
                else if(in_array($sch->status, ['Absent'])) $rowClass = 'absent';
                else if($sch->actual_time_in && $sch->actual_time_out) $rowClass = 'perfect';

                // Calculate late
                $late = '0:00';
                if($sch->actual_time_in && $sch->time_in && $sch->status === 'Scheduled'){
                    $lateMinutes = \Carbon\Carbon::parse($sch->actual_time_in)->diffInMinutes(\Carbon\Carbon::parse($sch->time_in), false);
                    if($lateMinutes > 0) $late = floor($lateMinutes / 60) . ':' . str_pad($lateMinutes % 60, 2, '0', STR_PAD_LEFT);
                }

                // Calculate undertime
                $undertime = '0:00';
                if($sch->actual_time_out && $sch->time_out && $sch->status === 'Scheduled'){
                    $undertimeMinutes = \Carbon\Carbon::parse($sch->time_out)->diffInMinutes(\Carbon\Carbon::parse($sch->actual_time_out), false);
                    if($undertimeMinutes > 0) $undertime = floor($undertimeMinutes / 60) . ':' . str_pad($undertimeMinutes % 60, 2, '0', STR_PAD_LEFT);
                }
            @endphp

            <tr class="{{ $rowClass }}">
                <td>{{ \Carbon\Carbon::parse($sch->schedule_date)->format('M d, Y') }}</td>
                <td>{{ $sch->shift }}</td>
                <td>{{ $sch->time_in ?? '-' }}</td>
                <td>{{ $sch->time_out ?? '-' }}</td>
                <td>{{ $sch->actual_time_in ?? '-' }}</td>
                <td>{{ $sch->actual_time_out ?? '-' }}</td>
                <td>{{ $late }}</td>
                <td>{{ $undertime }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<div class="totals">
    Total Late: {{ $totalLate }} | Total Undertime: {{ $totalUndertime }}
</div>

<div class="footer">
    <div>
        <p>__________________________</p>
        <p>Employee Signature</p>
    </div>
    <div>
        <p>__________________________</p>
        <p>HR Signature</p>
    </div>
</div>

</body>
</html>
