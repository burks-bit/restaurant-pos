<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use App\Models\Branch;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Mpdf\Mpdf;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Log;

class VoucherController extends Controller
{
    public function index()
    {
        $vouchers = Voucher::with('order')->latest()->get();
        return Inertia::render('GiftVouchers/Index', compact('vouchers'));
    }

    public function store(Request $request)
    {
        Log::info('dsadsa');
        Log::info($request->all());
        $request->validate([
            'control_no' => 'required|unique:vouchers,control_no',
            'type' => 'required|in:20%,30%,40%,50%,Free Meal',
            'validity' => 'required|date',
        ]);

        Voucher::create([
            'control_no' => $request->control_no,
            'type' => $request->type,
            'status' => 'available',
            'validity' => $request->validity,
        ]);

        return redirect()->back()->with('success', 'Voucher created successfully.');
    }

    public function update(Request $request, Voucher $voucher)
    {
        $request->validate([
            'control_no' => 'required|unique:vouchers,control_no,' . $voucher->id,
            'type' => 'required|in:20%,30%,40%,50%,Free Meal',
            'status' => 'required|in:available,used,expired',
        ]);

        $voucher->update($request->only('control_no', 'type', 'status'));

        return redirect()->back()->with('success', 'Voucher updated successfully.');
    }

    public function destroy(Voucher $voucher)
    {
        $voucher->delete();
        return redirect()->back()->with('success', 'Voucher deleted successfully.');
    }

    public function print(Voucher $voucher)
    {
        // Generate HTML content for PDF
        
        $branch = Branch::where('main', 1)->first();
        $html = View::make('gift_vouchers.gift_voucher', compact('voucher', 'branch'))->render();

        // Half bond paper dimensions: ~8.5 x 5.5 inches
        $mpdf = new Mpdf([
            'format' => [140, 216], // Width x Height in mm for half bond
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
        ]);

        $mpdf->WriteHTML($html);
        $mpdf->Output("Voucher-{$voucher->control_no}.pdf", 'I'); // I = Inline display in browser
    }

    public function printAll()
    {
        $vouchers = Voucher::orderBy('id')->where('status', 'available')->limit(2)->get();
        $branch = Branch::where('main', 1)->first();

        $html = view('gift_vouchers.print_all_voucher', compact('vouchers', 'branch'))->render();

        $mpdf = new Mpdf([
            'format' => 'Letter-L',
            'margin_top' => 5,
            'margin_bottom' => 5,
            'margin_left' => 8,
            'margin_right' => 8,
        ]);

        // $watermarkPath = public_path('app/public/web_images/hsb1v2.jpg'); // or 
        $watermarkPath = storage_path('app/public/web_images/voucherbgv5.png');
        $mpdf->SetWatermarkImage($watermarkPath, 0.2);
        $mpdf->showWatermarkImage = true;

        $mpdf->WriteHTML($html);
        return $mpdf->Output('vouchers.pdf', 'I');
    }
}
