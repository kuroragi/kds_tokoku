<?php

namespace App\Http\Controllers;

use App\Models\BusinessUnit;
use App\Models\Plan;
use App\Services\SubscriptionService;
use App\Services\VoucherService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OnboardingController extends Controller
{
    /**
     * Subscribe to a plan (simulated payment for now).
     */
    public function subscribe(Request $request, SubscriptionService $subscriptionService)
    {
        $request->validate([
            'plan' => ['required', 'string', 'exists:plans,slug'],
        ]);

        $user = $request->user();

        // Already has active subscription?
        if ($subscriptionService->hasActiveSubscription($user)) {
            return redirect()->route('onboarding.setup-instance')
                ->with('info', 'Anda sudah memiliki paket aktif.');
        }

        $plan = Plan::where('slug', $request->plan)->firstOrFail();

        $subscriptionService->createSubscription(
            user: $user,
            plan: $plan,
            paymentMethod: $plan->price > 0 ? 'pending' : 'free',
        );

        return redirect()->route('onboarding.setup-instance')
            ->with('success', "Paket {$plan->name} berhasil diaktifkan! Sekarang buat instansi bisnis Anda.");
    }

    /**
     * Redeem a voucher to get a plan.
     */
    public function redeemVoucher(Request $request, VoucherService $voucherService)
    {
        $request->validate([
            'code' => ['required', 'string', 'max:20'],
        ], [
            'code.required' => 'Kode voucher wajib diisi.',
        ]);

        $result = $voucherService->redeem($request->code, $request->user());

        if ($result['success']) {
            return redirect()->route('onboarding.setup-instance')
                ->with('success', $result['message'] . ' Sekarang buat instansi bisnis Anda.');
        }

        return back()->with('voucher_error', $result['message']);
    }

    /**
     * Show instance setup page.
     */
    public function showSetupInstance(SubscriptionService $subscriptionService)
    {
        $user = auth()->user();

        // Must have a subscription first
        if (!$subscriptionService->hasActiveSubscription($user)) {
            return redirect()->route('landing')
                ->with('error', 'Silakan pilih paket terlebih dahulu.');
        }

        // Already has instance?
        if ($user->business_unit_id) {
            return redirect()->route('dashboard');
        }

        $plan = $subscriptionService->getCurrentPlan($user);

        return view('onboarding.setup-instance', compact('plan'));
    }

    /**
     * Store the new business instance.
     */
    public function storeInstance(Request $request, SubscriptionService $subscriptionService)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'business_type' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
        ], [
            'name.required' => 'Nama bisnis wajib diisi.',
            'business_type.required' => 'Jenis bisnis wajib diisi.',
        ]);

        $user = $request->user();

        // Must have a subscription
        if (!$subscriptionService->hasActiveSubscription($user)) {
            return redirect()->route('landing')
                ->with('error', 'Silakan pilih paket terlebih dahulu.');
        }

        // Already has instance?
        if ($user->business_unit_id) {
            return redirect()->route('dashboard');
        }

        return DB::transaction(function () use ($validated, $user) {
            // Generate unique code
            $code = 'BU-' . strtoupper(substr(md5($user->id . now()->timestamp), 0, 6));

            $businessUnit = BusinessUnit::create([
                'code' => $code,
                'name' => $validated['name'],
                'owner_name' => $user->name,
                'phone' => $validated['phone'],
                'email' => $user->email,
                'address' => $validated['address'],
                'city' => $validated['city'],
                'province' => $validated['province'],
                'business_type' => $validated['business_type'],
                'is_active' => true,
            ]);

            // Assign user to the business unit
            $user->update(['business_unit_id' => $businessUnit->id]);

            // Assign pemilik role
            if (!$user->hasRole('superadmin')) {
                $user->assignRole('pemilik');
            }

            return redirect()->route('dashboard')
                ->with('success', "Selamat datang di Tokoku ERP! Instansi \"{$businessUnit->name}\" berhasil dibuat.");
        });
    }
}
