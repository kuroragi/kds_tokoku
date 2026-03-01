<?php

namespace App\Contracts;

use App\Models\PaymentTransaction;

/**
 * Contract for Payment Gateway drivers.
 *
 * Setiap driver (Manual, Midtrans, Xendit, dll.) harus mengimplementasi
 * interface ini agar bisa digunakan secara plug-and-play oleh PaymentService.
 */
interface PaymentGatewayInterface
{
    /**
     * Get the gateway identifier (e.g. 'manual', 'midtrans', 'xendit').
     */
    public function getIdentifier(): string;

    /**
     * Create a new payment request.
     *
     * Mengembalikan PaymentTransaction yang sudah tersimpan di database.
     * Untuk gateway online, gateway_data akan berisi snap_token / redirect_url / VA number dll.
     *
     * @param array{
     *     payable_type: string,
     *     payable_id: int,
     *     user_id: int,
     *     amount: float|string,
     *     currency?: string,
     *     payment_method?: string,
     *     payment_channel?: string,
     *     notes?: string,
     *     metadata?: array,
     * } $data
     */
    public function createPayment(array $data): PaymentTransaction;

    /**
     * Handle incoming callback/webhook from the payment provider.
     *
     * Menerima raw payload dari gateway dan mengupdate status transaksi.
     * Untuk manual gateway, ini dipanggil saat admin mengonfirmasi.
     *
     * @param array $payload  Raw data dari gateway / admin action
     * @return PaymentTransaction  The updated transaction
     */
    public function handleCallback(array $payload): PaymentTransaction;

    /**
     * Check the current status of a transaction with the payment provider.
     *
     * Berguna untuk sinkronisasi status jika callback tidak diterima.
     * Untuk manual gateway, ini hanya mengembalikan status lokal.
     *
     * @param PaymentTransaction $transaction
     * @return PaymentTransaction  The refreshed transaction
     */
    public function checkStatus(PaymentTransaction $transaction): PaymentTransaction;

    /**
     * Cancel/void a pending payment.
     *
     * @param PaymentTransaction $transaction
     * @param string|null $reason
     * @return PaymentTransaction  The cancelled transaction
     */
    public function cancel(PaymentTransaction $transaction, ?string $reason = null): PaymentTransaction;

    /**
     * Determine if the gateway supports automatic expiration handling.
     */
    public function supportsAutoExpiry(): bool;

    /**
     * Get the list of supported payment methods for this gateway.
     *
     * @return string[]  e.g. ['transfer', 'va_bca', 'va_mandiri', 'qris']
     */
    public function supportedMethods(): array;
}
