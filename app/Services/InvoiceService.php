<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;

class InvoiceService
{
    /**
     * Create an invoice for a pending subscription.
     */
    public function createForSubscription(Subscription $subscription): Invoice
    {
        $plan = $subscription->plan;

        // Cancel any unpaid invoices for this user
        Invoice::where('user_id', $subscription->user_id)
            ->where('status', 'unpaid')
            ->update(['status' => 'cancelled']);

        return Invoice::create([
            'invoice_number' => Invoice::generateNumber(),
            'user_id' => $subscription->user_id,
            'subscription_id' => $subscription->id,
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'plan_price' => $plan->price,
            'duration_days' => $plan->duration_days,
            'subtotal' => $plan->price,
            'discount' => 0,
            'tax' => 0,
            'total' => $plan->price,
            'status' => 'unpaid',
            'issued_at' => now()->toDateString(),
            'due_at' => now()->addDays(3)->toDateString(), // 3 days to pay
        ]);
    }

    /**
     * Mark invoice as paid when subscription is activated.
     */
    public function markPaid(Subscription $subscription, string $paymentMethod = 'transfer', ?string $reference = null): void
    {
        $invoice = Invoice::where('subscription_id', $subscription->id)
            ->where('status', 'unpaid')
            ->first();

        if ($invoice) {
            $invoice->markAsPaid($paymentMethod, $reference);
        }
    }

    /**
     * Cancel invoice when subscription is cancelled.
     */
    public function cancelForSubscription(Subscription $subscription): void
    {
        Invoice::where('subscription_id', $subscription->id)
            ->where('status', 'unpaid')
            ->update(['status' => 'cancelled']);
    }

    /**
     * Get active (unpaid) invoice for a subscription.
     */
    public function getUnpaidInvoice(Subscription $subscription): ?Invoice
    {
        return Invoice::where('subscription_id', $subscription->id)
            ->where('status', 'unpaid')
            ->first();
    }
}
