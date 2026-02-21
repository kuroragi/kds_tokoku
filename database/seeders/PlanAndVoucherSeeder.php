<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\PlanFeature;
use App\Services\VoucherService;
use Illuminate\Database\Seeder;

class PlanAndVoucherSeeder extends Seeder
{
    /**
     * Daftar semua feature keys yang tersedia di sistem.
     */
    private array $allFeatures = [
        'coa'                => 'COA & Jurnal',
        'general_ledger'     => 'Buku Besar',
        'trial_balance'      => 'Neraca Saldo & Laba Rugi',
        'master_data'        => 'Master Data (Stok/Customer/Vendor)',
        'purchase'           => 'Pembelian (Purchase)',
        'sales'              => 'Penjualan (Sales)',
        'ap_ar'              => 'Hutang/Piutang (AP/AR)',
        'bank'               => 'Bank & Transfer Dana',
        'saldo'              => 'Saldo Management',
        'pdf_reports'        => 'PDF Reports',
        'asset'              => 'Asset Management',
        'payroll'            => 'Payroll (Penggajian)',
        'employee_loan'      => 'Pinjaman Karyawan',
        'tax'                => 'Perpajakan (SPT/Faktur)',
        'opening_balance'    => 'Saldo Awal',
        'stock_opname'       => 'Stock & Saldo Opname',
        'export_excel'       => 'Export Excel',
        'multi_role'         => 'Multi-Role & Permission',
        'bank_reconciliation'=> 'Bank Reconciliation',
        'project'            => 'Project / Job Order',
        'dashboard_advanced' => 'Dashboard Advanced',
    ];

    /**
     * Feature matrix per plan.
     */
    private function getEnabledFeatures(string $planSlug): array
    {
        $trial = ['coa', 'general_ledger', 'trial_balance', 'master_data'];

        $basic = array_merge($trial, [
            'purchase', 'sales', 'ap_ar', 'bank', 'saldo', 'pdf_reports',
        ]);

        $medium = array_merge($basic, [
            'asset', 'payroll', 'employee_loan', 'tax', 'opening_balance',
            'stock_opname', 'export_excel', 'multi_role', 'dashboard_advanced',
        ]);

        $premium = array_merge($medium, [
            'bank_reconciliation', 'project',
        ]);

        return match ($planSlug) {
            'trial'   => $trial,
            'basic'   => $basic,
            'medium'  => $medium,
            'premium' => $premium,
            default   => [],
        };
    }

    public function run(): void
    {
        // ── Create Plans ──
        $plans = [
            [
                'name' => 'Trial',
                'slug' => 'trial',
                'price' => 0,
                'duration_days' => 14,
                'max_users' => 1,
                'max_business_units' => 1,
                'description' => 'Coba gratis selama 14 hari. Akses fitur dasar akuntansi.',
                'sort_order' => 1,
            ],
            [
                'name' => 'Basic',
                'slug' => 'basic',
                'price' => 99000,
                'duration_days' => 30,
                'max_users' => 3,
                'max_business_units' => 1,
                'description' => 'Untuk UMKM kecil. Akses lengkap pembelian, penjualan, dan keuangan.',
                'sort_order' => 2,
            ],
            [
                'name' => 'Medium',
                'slug' => 'medium',
                'price' => 249000,
                'duration_days' => 30,
                'max_users' => 10,
                'max_business_units' => 3,
                'description' => 'Untuk UMKM berkembang. Semua fitur termasuk payroll, pajak, dan aset.',
                'sort_order' => 3,
            ],
            [
                'name' => 'Premium',
                'slug' => 'premium',
                'price' => 499000,
                'duration_days' => 30,
                'max_users' => 0, // unlimited
                'max_business_units' => 0, // unlimited
                'description' => 'Untuk bisnis besar. Semua fitur tanpa batas, termasuk bank reconciliation & project.',
                'sort_order' => 4,
            ],
        ];

        foreach ($plans as $planData) {
            $plan = Plan::updateOrCreate(
                ['slug' => $planData['slug']],
                $planData
            );

            // Create features for this plan
            $enabledFeatures = $this->getEnabledFeatures($plan->slug);

            foreach ($this->allFeatures as $key => $label) {
                PlanFeature::updateOrCreate(
                    ['plan_id' => $plan->id, 'feature_key' => $key],
                    [
                        'feature_label' => $label,
                        'is_enabled' => in_array($key, $enabledFeatures),
                    ]
                );
            }
        }

        // ── Create Vouchers ──
        $voucherService = app(VoucherService::class);

        // 5 voucher testing Medium (3 bulan)
        $mediumPlan = Plan::where('slug', 'medium')->first();
        $mediumVouchers = $voucherService->createTestingVouchers($mediumPlan, 5, 90);

        // 5 voucher testing Premium (3 bulan)
        $premiumPlan = Plan::where('slug', 'premium')->first();
        $premiumVouchers = $voucherService->createTestingVouchers($premiumPlan, 5, 90);

        // 1 voucher khusus owner (Premium, lifetime)
        $ownerVoucher = $voucherService->createOwnerVoucher($premiumPlan);

        // Output for artisan
        $this->command->info('');
        $this->command->info('═══════════════════════════════════════════');
        $this->command->info('  PLANS & VOUCHERS SEEDED SUCCESSFULLY');
        $this->command->info('═══════════════════════════════════════════');
        $this->command->info('');
        $this->command->info('📦 Plans: Trial, Basic (Rp 99K), Medium (Rp 249K), Premium (Rp 499K)');
        $this->command->info('');
        $this->command->info('🎫 Voucher Testing MEDIUM (3 bulan):');
        foreach ($mediumVouchers as $v) {
            $this->command->info("   → {$v->code}");
        }
        $this->command->info('');
        $this->command->info('🎫 Voucher Testing PREMIUM (3 bulan):');
        foreach ($premiumVouchers as $v) {
            $this->command->info("   → {$v->code}");
        }
        $this->command->info('');
        $this->command->info('👑 Voucher OWNER (Premium, Lifetime):');
        $this->command->info("   → {$ownerVoucher->code}");
        $this->command->info('');
        $this->command->info('═══════════════════════════════════════════');
    }
}
