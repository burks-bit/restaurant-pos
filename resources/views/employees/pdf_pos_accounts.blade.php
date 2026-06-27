<!DOCTYPE html>
<html>
<head>
    <title>{{ $type == 1 ? 'POS Accounts' : 'Employee Personal Details' }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10pt; }
        table { border-collapse: collapse; width: 100%; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 6px 8px; text-align: left; }
        th { background-color: #f0f0f0; }
        h3 { margin-bottom: 0; }
        p { margin-top: 2px; color: #555; }
    </style>
</head>
<body>

    @if($type == 1)

        <h3>POS Accounts</h3>
        <p>Generated: {{ \Carbon\Carbon::now()->format('M d, Y g:i A') }}</p>

        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email / Username</th>
                    <th>Default Password</th>
                </tr>
            </thead>
            <tbody>
                @foreach($userAccounts as $user)
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>1</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

    @elseif($type == 2)

        <h3>Employee Personal Details</h3>
        <p>Generated: {{ \Carbon\Carbon::now()->format('M d, Y g:i A') }}</p>

        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Contact Number</th>
                    <th>Contact Email</th>
                    <th>Address</th>
                </tr>
            </thead>
            <tbody>
                @foreach($employeeDetails as $emp)
                    <tr>
                        <td>{{ $emp->first_name }} {{ $emp->last_name }}</td>
                        <td>{{ $emp->contact_number ?? '-' }}</td>
                        <td>{{ $emp->email ?? '-' }}</td>
                        <td>{{ $emp->address ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

    @endif

</body>
</html>