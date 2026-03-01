<?php

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Invoice;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Services\PaymentGateways\ManualPaymentGateway;
use InvalidArgumentException;

/**
 * PaymentService
 *
 * Facade service yang mendelegasikan operasi pembayaran ke gateway driver yang aktif.
 * Saat ini hanya mendukung ManualPaymentGateway (transfer → konfirmasi admin).
 *
 * Untuk menambahkan gateway baru (misal Midtrans):
 *  1. Buat class App\Services\PaymentGateways\MidtransPaymentGateway implements PaymentGatewayInterface
 *  2. Daftarkan di $gateways array di bawah
 *  3. Set PAYMENT_GATEWAY=midtrans di .env
 *
 * Usage:
 *   $paymentService = app(PaymentService::class);
 *   $transaction = $paymentService->charge($invoice, $user);
 *   $paymentService->confirm($transaction->transaction_id, 'REF-123', $adminId);
 */
class PaymentService
{
    /**
     * Registry of available gateway drivers.
     * Key = gateway identifier, Value = class name
     *
     * @var array<string, class-string<PaymentGatewayInterface>>
     */
    protected static array $gateways = [
        'manual' => ManualPaymentGateway::class,
        // 'midtrans' => \App\Services\PaymentGateways\MidtransPaymentGateway::class,
        // 'xendit'   => \App\Services\PaymentGateways\XenditPaymentGateway::class,
    ];

    protected PaymentGatewayInterface $driver;

    public function __construct(?string $gateway = null)
    {
        $gatewayName = $gateway ?? config('services.payment.gateway', 'manual');
        $this->driver = $this->resolveDriver($gatewayName);
    }

    // ── Gateway Resolution ──

    protected function resolveDriver(string $name): PaymentGatewayInterface
    {
        if (! isset(static::$gateways[$name])) {
            throw new InvalidArgumentException("Payment gateway [{$name}] is not registered.");
        }

        return app(static::$gateways[$name]);
    }

    /**
     * Get the current active gateway driver.
     */
    public function driver(): PaymentGatewayInterface
    {
        return $this->driver;
    }

    /**
     * Use a specific gateway for this call.
     */
    public function using(string $gateway): static
    {
        $this->driver = $this->resolveDriver($gateway);
        return $this;
    }

    /**
     * Register a new gateway driver at runtime.
     *
     * @param string $name
     * @param class-string<PaymentGatewayInterface> $class
     */
    public static function registerGateway(string $name, string $class): void
    {
        static::$gateways[$name] = $class;
    }

    // ── Payment Operations ──

    /**
     * Initiate a payment for an invoice.
     *
     * Membuat PaymentTransaction baru dan mendelegasikan ke gateway driver.
     */
    public function charge(Invoice $invoice, ?User $user = null, array $options = []): PaymentTransaction
    {
        $user = $user ?? $invoice->user;

        return $this->driver->createPayment(array_merge([
            'payable_type' => Invoice::class,
            'payable_id' => $invoice->id,
            'user_id' => $user->id,
            'amount' => $invoice->total,
        ], $options));
    }

    /**
     * Confirm a payment (used for manual gateway / admin confirmation).
     */
    public function confirm(
        string $transactionId,
        ?string $reference = null,
        ?int $confirmedBy = null,
        ?string $notes = null
    ): PaymentTransaction {
        return $this->driver->handleCallback([
            'transaction_id' => $transactionId,
            'reference' => $reference,
            'confirmed_by' => $confirmedBy,
            'notes' => $notes,
        ]);
    }

    /**
     * Handle webhook/callback payload from external gateway.
     */
    public function handleWebhook(array $payload): PaymentTransaction
    {
        return $this->driver->handleCallback($payload);
    }

    /**
     * Check the current status of a transaction with the gateway.
     */
    public function checkStatus(PaymentTransaction $transaction): PaymentTransaction
    {
        return $this->driver->checkStatus($transaction);
    }

    /**
     * Cancel a pending transaction.
     */
    public function cancel(PaymentTransaction $transaction, ?string $reason = null): PaymentTransaction
    {
        return $this->driver->cancel($transaction, $reason);
    }

    /**
     * Get the active gateway identifier.
     */
    public function getActiveGateway(): string
    {
        return $this->driver->getIdentifier();
    }

    /**
     * Get supported payment methods from the active gateway.
     *
     * @return string[]
     */
    public function supportedMethods(): array
    {
        return $this->driver->supportedMethods();
    }

    /**
     * Get all registered gateway names.
     *
     * @return string[]
     */
    public static function availableGateways(): array
    {
        return array_keys(static::$gateways);
    }
}
