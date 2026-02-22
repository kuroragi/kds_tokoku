<?php

namespace App\Livewire;

use App\Mail\SubscriptionActivatedMail;
use App\Models\Subscription;
use App\Services\InvoiceService;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;
use Livewire\WithPagination;

class SubscriptionManagement extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public string $search = '';
    public string $filterStatus = '';

    // Activate modal
    public bool $showActivateModal = false;
    public ?int $activateId = null;
    public string $paymentReference = '';

    protected $queryString = ['search', 'filterStatus'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function getSubscriptionsProperty()
    {
        $query = Subscription::with(['user', 'plan'])
            ->orderByRaw("FIELD(status, 'pending', 'active', 'grace', 'expired', 'cancelled')")
            ->orderByDesc('created_at');

        if ($this->search) {
            $query->whereHas('user', function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%")
                  ->orWhere('username', 'like', "%{$this->search}%");
            });
        }

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        return $query->paginate(15);
    }

    public function openActivateModal(int $id): void
    {
        $this->activateId = $id;
        $this->paymentReference = '';
        $this->showActivateModal = true;
    }

    public function activateSubscription(): void
    {
        $subscription = Subscription::findOrFail($this->activateId);

        $subscription->update([
            'status' => 'active',
            'payment_method' => 'transfer',
            'payment_reference' => $this->paymentReference ?: 'Dikonfirmasi Admin',
            'starts_at' => now()->toDateString(),
            'ends_at' => now()->addDays($subscription->plan->duration_days)->toDateString(),
        ]);

        // Mark the invoice as paid
        $invoiceService = app(InvoiceService::class);
        $invoiceService->markPaid($subscription, 'transfer', $this->paymentReference ?: 'Dikonfirmasi Admin');

        // Send activation email to user
        try {
            Mail::to($subscription->user->email)
                ->send(new SubscriptionActivatedMail($subscription));
        } catch (\Exception $e) {
            // Log but don't fail activation
            \Log::warning('Failed to send activation email: ' . $e->getMessage());
        }

        $this->showActivateModal = false;
        $this->activateId = null;
        $this->paymentReference = '';

        session()->flash('success', "Langganan untuk {$subscription->user->name} berhasil diaktifkan.");
    }

    public function cancelSubscription(int $id): void
    {
        $subscription = Subscription::findOrFail($id);
        $subscription->update(['status' => 'cancelled']);

        // Cancel related invoice
        $invoiceService = app(InvoiceService::class);
        $invoiceService->cancelForSubscription($subscription);

        session()->flash('success', "Langganan untuk {$subscription->user->name} dibatalkan.");
    }

    public function render()
    {
        return view('livewire.subscription-management');
    }
}
