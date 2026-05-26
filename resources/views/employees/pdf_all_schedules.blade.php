<!DOCTYPE html>
<html>
<head>
    <title>All Employees Schedule</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 9pt; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #000; padding: 4px; text-align: center; font-size: 7pt; }
        th { background-color: #f0f0f0; }
        .employee-name { text-align: left; padding-left: 6px; font-weight: bold; }

        /* Shift colors */
        .morning { background-color: #d4edda; } /* Greenish */
        .night { background-color: #cce5ff; }   /* Blueish */

        /* Status colors */
        .Scheduled { } /* default, no extra color */
        .Absent { background-color: #f8d7da; color: #721c24; } /* redish */
        .Leave { background-color: #fff3cd; color: #856404; }  /* yellowish */
        .DayOff { background-color: #e2e3e5; color: #6c757d; } /* grayish */

        .remarks { font-size: 6pt; font-style: italic; color: #333; margin-top: 2px; display: block; }
    </style>
</head>
<body>

    <h3>All Employees Schedules</h3>
    <p>Period: {{ \Carbon\Carbon::parse($dates[0])->format('M d, Y') }} - {{ \Carbon\Carbon::parse(end($dates))->format('M d, Y') }}</p>

    <table>
        <thead>
            <tr>
                <th>Employee</th>
                @foreach($dates as $date)
                    <th>{{ $date->format('d') }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($employees as $emp)
                <tr>
                    <td class="employee-name">{{ $emp->first_name }} {{ $emp->last_name }}</td>
                    @foreach($dates as $date)
                        @php
                            $schedule = $emp->schedules->first(function($s) use ($date) {
                                return \Carbon\Carbon::parse($s->schedule_date)->toDateString() === $date->toDateString();
                            });
                        @endphp
                        <td class="{{ $schedule ? strtolower($schedule->shift) : '' }} {{ $schedule ? str_replace(' ', '', $schedule->status) : '' }}">
                            @if($schedule)
                                {{ \Carbon\Carbon::parse($schedule->time_in)->format('g:i A') }}
                                - {{ \Carbon\Carbon::parse($schedule->time_out)->format('g:i A') }}
                                <span class="remarks">
                                    {{ $schedule->status }}{{ $schedule->remarks && trim($schedule->remarks) !== '' ? ': ' . $schedule->remarks : '' }}
                                </span>
                            @else
                                -
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>
