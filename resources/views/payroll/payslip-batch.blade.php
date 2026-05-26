<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    .page-break { page-break-after: always; }
  </style>
</head>
<body>

  @foreach($items as $item)
    @include('payroll.payslip-content', [
        'payroll' => $payroll,
        'item'    => $item,
        'branch'  => $branch,
        'cutoff'  => $cutoff,
    ])

    @if(!$loop->last)
      <div class="page-break"></div>
    @endif
  @endforeach

</body>
</html>