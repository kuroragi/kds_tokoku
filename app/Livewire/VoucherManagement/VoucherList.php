<?php

namespace App\Livewire\VoucherManagement;

use App\Mail\VoucherMail;
use App\Models\Plan;
use App\Models\Voucher;
use App\Services\VoucherService;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;
use Livewire\WithPagination;

class VoucherList extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    // Filters
    public string $search = '';
    public string $filterPlan = '';
    public string $filterType = '';
    public string $filterStatus = '';

    // Create form
    public bool $showCreateModal = false;
    public string $createPlanId = '';
    public string $createType = 'promo';
    public int $createDuration = 30;
    public int $createMaxUses = 1;
    public int $createQuantity = 1;
    public string $createDescription = '';

    // Send email
    public bool $showSendModal = false;
    public ?int $sendVoucherId = null;
    public string $sendEmail = '';
    public string $sendName = '';
    public string $sendMessage = '';

    // Detail
    public bool $showDetailModal = false;
    public ?Voucher $detailVoucher = null;

    protected $queryString = ['search', 'filterPlan', 'filterType', 'filterStatus'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterPlan(): void
    {
        $this->resetPage();
    }

    public function updatingFilterType(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    // ── Computed Properties ──

    public function getVouchersProperty()
    {
        return Voucher::with(['plan', 'redemptions.user'])
            ->when($this->search, fn($q) => $q->where(function ($q) {
                $q->where('code', 'like', "%{$this->search}%")
                  ->orWhere('description', 'like', "%{$this->search}%");
            }))
            ->when($this->filterPlan, fn($q) => $q->where('plan_id', $this->filterPlan))
            ->when($this->filterType, fn($q) => $q->where('type', $this->filterType))
            ->when($this->filterStatus, function ($q) {
                if ($this->filterStatus === 'active') {
                    return $q->active()->valid()->whereColumn('used_count', '<', 'max_uses');
                } elseif ($this->filterStatus === 'expired') {
                    return $q->where('valid_until', '<', now()->toDateString());
                } elseif ($this->filterStatus === 'used_up') {
                    return $q->whereColumn('used_count', '>=', 'max_uses');
                } elseif ($this->filterStatus === 'inactive') {
                    return $q->where('is_active', false);
                }
                return $q;
            })
            ->orderByDesc('created_at')
            ->paginate(15);
    }

    public function getPlansProperty()
    {
        return Plan::active()->ordered()->get();
    }

    public function getSummaryProperty(): array
    {
        return [
            'total' => Voucher::count(),
            'active' => Voucher::active()->valid()->whereColumn('used_count', '<', 'max_uses')->count(),
            'used_up' => Voucher::whereColumn('used_count', '>=', 'max_uses')->count(),
            'expired' => Voucher::where('valid_until', '<', now()->toDateString())->count(),
        ];
    }

    // ── Create Actions ──

    public function openCreate(): void
    {
        $this->reset(['createPlanId', 'createType', 'createDuration', 'createMaxUses', 'createQuantity', 'createDescription']);
        $this->createType = 'promo';
        $this->createDuration = 30;
        $this->createMaxUses = 1;
        $this->createQuantity = 1;
        $this->showCreateModal = true;
    }

    public function closeCreate(): void
    {
        $this->showCreateModal = false;
    }

    public function generateVouchers(): void
    {
        $this->validate([
            'createPlanId' => 'required|exists:plans,id',
            'createType' => 'required|in:testing,promo,owner',
            'createDuration' => 'required|integer|min:1|max:36500',
            'createMaxUses' => 'required|integer|min:1|max:10000',
            'createQuantity' => 'required|integer|min:1|max:100',
            'createDescription' => 'nullable|string|max:255',
        ]);

        $service = app(VoucherService::class);
        $plan = Plan::findOrFail($this->createPlanId);

        $created = [];
        for ($i = 0; $i < $this->createQuantity; $i++) {
            $created[] = $service->createVoucher(
                plan: $plan,
                type: $this->createType,
                durationDays: $this->createDuration,
                maxUses: $this->createMaxUses,
                description: $this->createDescription ?: "Voucher {$this->createType} — {$plan->name}",
            );
        }

        $this->showCreateModal = false;
        session()->flash('success', "Berhasil membuat {$this->createQuantity} voucher untuk paket {$plan->name}.");
    }

    // ── Send Email Actions ──

    public function openSend(int $id): void
    {
        $this->sendVoucherId = $id;
        $this->reset(['sendEmail', 'sendName', 'sendMessage']);
        $this->showSendModal = true;
    }

    public function closeSend(): void
    {
        $this->showSendModal = false;
        $this->sendVoucherId = null;
    }

    public function sendVoucherEmail(): void
    {
        $this->validate([
            'sendEmail' => 'required|email',
            'sendName' => 'required|string|max:100',
            'sendMessage' => 'nullable|string|max:500',
        ]);

        $voucher = Voucher::with('plan')->findOrFail($this->sendVoucherId);

        Mail::to($this->sendEmail)->queue(new VoucherMail(
            voucher: $voucher,
            recipientName: $this->sendName,
            personalMessage: $this->sendMessage,
        ));

        $this->showSendModal = false;
        session()->flash('success', "Voucher {$voucher->code} berhasil dikirim ke {$this->sendEmail}.");
    }

    // ── Detail / Toggle Actions ──

    public function openDetail(int $id): void
    {
        $this->detailVoucher = Voucher::with(['plan', 'redemptions.user'])->findOrFail($id);
        $this->showDetailModal = true;
    }

    public function closeDetail(): void
    {
        $this->showDetailModal = false;
        $this->detailVoucher = null;
    }

    public function toggleActive(int $id): void
    {
        $voucher = Voucher::findOrFail($id);
        $voucher->update(['is_active' => !$voucher->is_active]);

        $status = $voucher->is_active ? 'diaktifkan' : 'dinonaktifkan';
        session()->flash('success', "Voucher {$voucher->code} berhasil {$status}.");
    }

    public function deleteVoucher(int $id): void
    {
        $voucher = Voucher::findOrFail($id);

        if ($voucher->used_count > 0) {
            session()->flash('error', 'Voucher yang sudah pernah digunakan tidak bisa dihapus.');
            return;
        }

        $code = $voucher->code;
        $voucher->delete();
        session()->flash('success', "Voucher {$code} berhasil dihapus.");
    }

    public function render()
    {
        return view('livewire.voucher-management.voucher-list');
    }
}
