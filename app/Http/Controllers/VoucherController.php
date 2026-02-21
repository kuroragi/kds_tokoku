<?php

namespace App\Http\Controllers;

use App\Services\VoucherService;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    public function redeem(Request $request, VoucherService $voucherService)
    {
        $request->validate([
            'code' => ['required', 'string', 'max:20'],
        ], [
            'code.required' => 'Kode voucher wajib diisi.',
        ]);

        $result = $voucherService->redeem($request->code, $request->user());

        if ($result['success']) {
            return back()->with('voucher_success', $result['message']);
        }

        return back()->with('voucher_error', $result['message']);
    }
}
