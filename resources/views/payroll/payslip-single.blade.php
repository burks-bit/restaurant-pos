<!doctype html>
<html>
<head><meta charset="utf-8"></head>
<body>
  @include('payroll.payslip-content', [
      'payroll' => $payroll,
      'item'    => $item,
      'branch'  => $branch,
      'cutoff'  => $cutoff,
  ])
</body>
</html>