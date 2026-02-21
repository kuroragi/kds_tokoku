<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Voucher;
use App\Models\VoucherRedemption;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VoucherService
{
    public function __construct(
        private SubscriptionService $subscriptionService
    ) {}

    /**
     * Generate a random voucher code.
     * Format: TOKOKU-XXXXX-XXXXX (uppercase alphanumeric)
     */
    public function generateCode(string $prefix = 'TOKOKU'): string
    {
        do {
            $code = $prefix . '-' . strtoupper(Str::random(5)) . '-' . strtoupper(Str::random(5));
        } while (Voucher::where('code', $code)->exists());

        return $code;
    }

    /**
     * Create a single voucher.
     */
    public function createVoucher(
        Plan $plan,
        string $type = 'testing',
        int $durationDays = 90,
        int $maxUses = 1,
        ?string $description = null,
        ?string $code = null
    ): Voucher {
        return Voucher::create([
            'code' => $code ?? $this->generateCode(),
            'plan_id' => $plan->id,
            'duration_days' => $durationDays,
            'max_uses' => $maxUses,
            'used_count' => 0,
            'valid_from' => now()->toDateString(),
            'valid_until' => now()->addYear()->toDateString(),
            'is_active' => true,
            'description' => $description,
            'type' => $type,
        ]);
    }

    /**
     * Create multiple testing vouchers.
     */
    public function createTestingVouchers(Plan $plan, int $count = 5, int $durationDays = 90): array
    {
        $vouchers = [];
        for ($i = 0; $i < $count; $i++) {
            $vouchers[] = $this->createVoucher(
                plan: $plan,
                type: 'testing',
                durationDays: $durationDays,
                description: "Voucher testing {$plan->name} #{$i}",
            );
        }

        return $vouchers;
    }

    /**
     * Create a special owner voucher (unlimited use, longer duration).
     */
    public function createOwnerVoucher(Plan $plan): Voucher
    {
        return $this->createVoucher(
            plan: $plan,
            type: 'owner',
            durationDays: 36500, // ~100 tahun (selamanya)
            maxUses: 1,
            description: 'Voucher khusus pemilik aplikasi — lifetime Premium',
            code: 'OWNER-PREMIUM-KDS',
        );
    }

    /**
     * Redeem a voucher for a user.
     */
    public function redeem(string $code, User $user): array
    {
        $voucher = Voucher::where('code', strtoupper($code))->first();

        if (!$voucher) {
            return ['success' => false, 'message' => 'Kode voucher tidak ditemukan.'];
        }

        if (!$voucher->isValid()) {
            if ($voucher->isFullyRedeemed()) {
                return ['success' => false, 'message' => 'Voucher sudah habis digunakan.'];
            }
            if (!$voucher->is_active) {
                return ['success' => false, 'message' => 'Voucher tidak aktif.'];
            }
            return ['success' => false, 'message' => 'Voucher sudah kadaluarsa.'];
        }

        if ($voucher->hasBeenRedeemedBy($user->id)) {
            return ['success' => false, 'message' => 'Anda sudah pernah menggunakan voucher ini.'];
        }

        return DB::transaction(function () use ($voucher, $user) {
            // Create subscription
            $subscription = $this->subscriptionService->createSubscription(
                user: $user,
                plan: $voucher->plan,
                paymentMethod: 'voucher',
                voucherCode: $voucher->code,
                durationDays: $voucher->duration_days
            );

            // Record redemption
            VoucherRedemption::create([
                'voucher_id' => $voucher->id,
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'redeemed_at' => now(),
            ]);

            // Increment used count
            $voucher->increment('used_count');

            return [
                'success' => true,
                'message' => "Voucher berhasil digunakan! Paket {$voucher->plan->name} aktif selama {$voucher->duration_days} hari.",
                'subscription' => $subscription,
            ];
        });
    }

    /**
     * Validate voucher without redeeming.
     */
    public function validate(string $code): array
    {
        $voucher = Voucher::with('plan')->where('code', strtoupper($code))->first();

        if (!$voucher) {
            return ['valid' => false, 'message' => 'Kode voucher tidak ditemukan.'];
        }

        if (!$voucher->isValid()) {
            return ['valid' => false, 'message' => 'Voucher tidak valid atau sudah kadaluarsa.'];
        }

        return [
            'valid' => true,
            'plan_name' => $voucher->plan->name,
            'duration_days' => $voucher->duration_days,
            'message' => "Voucher valid — Paket {$voucher->plan->name} ({$voucher->duration_days} hari)",
        ];
    }
}
