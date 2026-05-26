<?php

namespace App\Exports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromCollection;

class SalesExport implements FromCollection
{
    public function collection()
    {
        return Order::selectRaw('DATE(created_at) as date, SUM(total) as total')
            ->groupBy('date')
            ->get();
    }
}
