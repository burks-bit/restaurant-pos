<!DOCTYPE html>
<html>
<head>
    <title>{{ $employee->first_name }} {{ $employee->last_name }} - Schedule</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11pt; }
        .header { margin-bottom: 20px; }
        .employee-name { font-size: 14pt; font-weight: bold; }
        .details { font-size: 10pt; margin-top: 4px; }
        .date-range { margin-top: 6px; font-size: 10pt; }

        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #000; padding: 6px; text-align: left; font-size: 9pt; }
        th { background-color: #f0f0f0; }

        /* Status colors */
        .Scheduled { } /* default */
        .Absent { background-color: #f8d7da; color: #721c24; } /* redish */
        .Leave { background-color: #fff3cd; color: #856404; }  /* yellowish */
        .DayOff { background-color: #e2e3e5; color: #6c757d; } /* grayish */

        .remarks { font-size: 8pt; font-style: italic; color: #333; margin-top: 2px; display: block; }

        .footer {
            margin-top: 40px;
            font-size: 9pt;
            text-align: left;
        }
    </style>
</head>
<body>

    <!-- HEADER -->
    <div class="header">
        <div class="employee-name">
            {{ $employee->first_name }} {{ $employee->last_name }}
        </div>

        <div class="details">
            Employee Code: {{ $employee->employee_code }} <br>
            @if(isset($employee->position))
                Position: {{ $employee->position }} <br>
            @endif
            @if(isset($employee->department))
                Department: {{ $employee->department }}
            @endif
        </div>

        <div class="date-range">
            Schedule Period:
            {{ \Carbon\Carbon::parse($start)->format('F d, Y') }}
            -
            {{ \Carbon\Carbon::parse($end)->format('F d, Y') }}
        </div>
    </div>

    <!-- TABLE -->
    <table>
        <thead>
            <tr>
                <th width="20%">Date</th>
                <th width="15%">Shift</th>
                <th width="15%">Time In</th>
                <th width="15%">Time Out</th>
                <th width="20%">Status</th>
                <th width="15%">Remarks</th>
            </tr>
        </thead>
        <tbody>
            @forelse($schedules as $schedule)
                <tr class="{{ str_replace(' ', '', $schedule->status) }}">
                    <td>{{ \Carbon\Carbon::parse($schedule->schedule_date)->format('Y-m-d') }}</td>
                    <td>{{ $schedule->shift }}</td>
                    <td>{{ \Carbon\Carbon::parse($schedule->time_in)->format('H:i') }}</td>
                    <td>{{ \Carbon\Carbon::parse($schedule->time_out)->format('H:i') }}</td>
                    <td>{{ $schedule->status }}</td>
                    <td>{{ $schedule->remarks ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align:center;">No schedules found for selected period.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- FOOTER -->
    <div class="footer">
        Generated & Printed by: {{ $printed_by->name }} <br>
        Printed on: {{ now()->format('F d, Y h:i A') }}
    </div>

</body>
</html>
