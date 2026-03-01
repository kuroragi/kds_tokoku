<?php

namespace App\Services\PaymentGateways;

use App\Contracts\PaymentGatewayInterface;
use App\Models\PaymentTransaction;

/**
 * Manual Payment Gateway
 *
 * Driver untuk pembayaran manual (transfer bank → konfirmasi admin).
 * Ini adalah flow yang saat ini digunakan:
 *  1. User memilih plan → subscription pending + invoice unpaid
 *  2. User transfer manual & konfirmasi via WhatsApp
 *  3. Admin buka SubscriptionManagement → konfirmasi pembayaran
 *  4. Transaction ditandai success, invoice paid, subscription active
 */
class ManualPaymentGateway implements PaymentGatewayInterface
{
    public function getIdentifier(): string
    {
        return PaymentTransaction::GATEWAY_MANUAL;
    }

    /**
     * Create a pending manual payment transaction.
     *
     * Untuk manual payment, tidak ada redirect URL / snap token.
     * Transaction langsung berstatus 'pending' menunggu konfirmasi admin.
     */
    public function createPayment(array $data): PaymentTransaction
    {
        return PaymentTransaction::create([
            'transaction_id' => PaymentTransaction::generateTransactionId(),
            'gateway' => $this->getIdentifier(),
            'payable_type' => $data['payable_type'],
            'payable_id' => $data['payable_id'],
            'user_id' => $data['user_id'],
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'IDR',
            'status' => PaymentTransaction::STATUS_PENDING,
            'payment_method' => $data['payment_method'] ?? 'transfer',
            'payment_channel' => $data['payment_channel'] ?? null,
            'notes' => $data['notes'] ?? null,
            'gateway_data' => [
                'instructions' => 'Silakan transfer ke rekening yang tertera, lalu konfirmasi via WhatsApp.',
            ],
        ]);
    }

    /**
     * Handle admin confirmation (acts as the "callback" for manual gateway).
     *
     * @param array{
     *     transaction_id: string,
     *     reference: ?string,
     *     confirmed_by: ?int,
     *     notes: ?string,
     * } $payload
     */
    public function handleCallback(array $payload): PaymentTransaction
    {
        $transaction = PaymentTransaction::where('transaction_id', $payload['transaction_id'])
            ->where('gateway', $this->getIdentifier())
            ->firstOrFail();

        if ($transaction->isFinalized()) {
            return $transaction;
        }

        $transaction->markAsSuccess(
            gatewayReference: $payload['reference'] ?? 'Dikonfirmasi Admin',
            callbackData: [
                'confirmed_by' => $payload['confirmed_by'] ?? null,
                'confirmed_at' => now()->toIso8601String(),
                'notes' => $payload['notes'] ?? null,
            ],
        );

        return $transaction->refresh();
    }

    /**
     * For manual gateway, just return the current local status.
     */
    public function checkStatus(PaymentTransaction $transaction): PaymentTransaction
    {
        return $transaction->refresh();
    }

    /**
     * Cancel a pending manual payment.
     */
    public function cancel(PaymentTransaction $transaction, ?string $reason = null): PaymentTransaction
    {
        if ($transaction->isFinalized()) {
            return $transaction;
        }

        $transaction->markAsCancelled($reason);
        return $transaction->refresh();
    }

    /**
     * Manual gateway does not auto-expire; admin harus cancel secara manual.
     */
    public function supportsAutoExpiry(): bool
    {
        return false;
    }

    public function supportedMethods(): array
    {
        return ['transfer'];
    }
}
