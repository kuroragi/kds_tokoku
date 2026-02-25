<div>
    @if (session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-double-line me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Filters --}}
    <div class="row mb-3 g-2">
        <div class="col-md-6">
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="ri-search-line"></i></span>
                <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Cari nama, email, username...">
            </div>
        </div>
        <div class="col-md-3">
            <select wire:model.live="filterStatus" class="form-select">
                <option value="">Semua Status</option>
                <option value="pending">Pending</option>
                <option value="active">Active</option>
                <option value="grace">Grace Period</option>
                <option value="expired">Expired</option>
                <option value="cancelled">Cancelled</option>
            </select>
        </div>
    </div>

    {{-- Stats --}}
    @php
        $allSubs = \App\Models\Subscription::selectRaw("
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
            COUNT(CASE WHEN status = 'active' THEN 1 END) as active_count,
            COUNT(*) as total_count
        ")->first();
    @endphp
    <div class="row mb-3 g-2">
        <div class="col-md-4">
            <div class="card bg-warning-subtle border-0 mb-0">
                <div class="card-body py-2 px-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="ri-time-line fs-4 text-warning"></i>
                        <div>
                            <div class="fw-bold">{{ $allSubs->pending_count }}</div>
                            <div class="text-muted small">Menunggu Pembayaran</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success-subtle border-0 mb-0">
                <div class="card-body py-2 px-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="ri-check-double-line fs-4 text-success"></i>
                        <div>
                            <div class="fw-bold">{{ $allSubs->active_count }}</div>
                            <div class="text-muted small">Aktif</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-primary-subtle border-0 mb-0">
                <div class="card-body py-2 px-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="ri-database-2-line fs-4 text-primary"></i>
                        <div>
                            <div class="fw-bold">{{ $allSubs->total_count }}</div>
                            <div class="text-muted small">Total Langganan</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Pending Alert with WhatsApp --}}
    @if($allSubs->pending_count > 0)
    <div class="alert alert-warning d-flex align-items-center gap-3 mb-3">
        <i class="ri-notification-3-line fs-3"></i>
        <div class="flex-grow-1">
            <strong>{{ $allSubs->pending_count }} langganan menunggu konfirmasi pembayaran.</strong>
            <div class="text-muted small">Hubungi pelanggan via WhatsApp untuk memverifikasi pembayaran.</div>
        </div>
    </div>
    @endif

    {{-- Table --}}
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>User</th>
                    <th>Paket</th>
                    <th>Status</th>
                    <th>Periode</th>
                    <th>Pembayaran</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($this->subscriptions as $sub)
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $sub->user->name ?? '-' }}</div>
                        <div class="text-muted small">{{ $sub->user->email ?? '-' }}</div>
                    </td>
                    <td>
                        <span class="badge bg-primary-subtle text-primary px-2 py-1">{{ $sub->plan->name ?? '-' }}</span>
                    </td>
                    <td>
                        @switch($sub->status)
                            @case('pending')
                                <span class="badge bg-warning-subtle text-warning"><i class="ri-time-line me-1"></i>Pending</span>
                                @break
                            @case('active')
                                <span class="badge bg-success-subtle text-success"><i class="ri-check-line me-1"></i>Active</span>
                                @break
                            @case('grace')
                                <span class="badge bg-info-subtle text-info"><i class="ri-alert-line me-1"></i>Grace</span>
                                @break
                            @case('expired')
                                <span class="badge bg-secondary-subtle text-secondary"><i class="ri-close-circle-line me-1"></i>Expired</span>
                                @break
                            @case('cancelled')
                                <span class="badge bg-danger-subtle text-danger"><i class="ri-close-line me-1"></i>Cancelled</span>
                                @break
                        @endswitch
                    </td>
                    <td>
                        <div class="small">{{ $sub->starts_at?->format('d/m/Y') ?? '-' }}</div>
                        <div class="small text-muted">s/d {{ $sub->ends_at?->format('d/m/Y') ?? '-' }}</div>
                    </td>
                    <td>
                        <div class="fw-semibold">Rp {{ number_format($sub->amount_paid, 0, ',', '.') }}</div>
                        <div class="text-muted small">{{ $sub->payment_method ?? '-' }}</div>
                    </td>
                    <td class="text-center">
                        @if($sub->status === 'pending')
                            @php
                                $userPhone = $sub->user->phone ?? '';
                                $waNumber = $userPhone ?: '';
                                $waText = "Halo " . ($sub->user->name ?? '') . ", kami dari TOKOKU.\n"
                                    . "Langganan Paket " . ($sub->plan->name ?? '') . " Anda sudah kami terima.\n"
                                    . "Total: Rp " . number_format($sub->amount_paid, 0, ',', '.') . "\n"
                                    . "Mohon kirimkan bukti transfer untuk kami proses. Terima kasih!";
                            @endphp
                            @if($waNumber)
                            <a href="https://wa.me/{{ $waNumber }}?text={{ urlencode($waText) }}"
                               target="_blank" class="btn btn-sm btn-outline-success me-1" title="Hubungi via WA">
                                <i class="ri-whatsapp-line"></i>
                            </a>
                            @endif
                            <button wire:click="openActivateModal({{ $sub->id }})" class="btn btn-sm btn-success me-1" title="Aktifkan">
                                <i class="ri-check-double-line"></i> Aktifkan
                            </button>
                            <button wire:click="cancelSubscription({{ $sub->id }})" class="btn btn-sm btn-outline-danger"
                                    wire:confirm="Yakin membatalkan langganan ini?"
                                    title="Batalkan">
                                <i class="ri-close-line"></i>
                            </button>
                        @elseif($sub->status === 'active')
                            <span class="text-muted small">{{ $sub->daysRemaining() }} hari tersisa</span>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        <i class="ri-inbox-line fs-3 d-block mb-2"></i>
                        Tidak ada data langganan.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $this->subscriptions->links() }}
    </div>

    {{-- Activate Modal --}}
    @if($showActivateModal)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ri-check-double-line text-success me-2"></i>Aktifkan Langganan</h5>
                    <button type="button" class="btn-close" wire:click="$set('showActivateModal', false)"></button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin mengaktifkan langganan ini? Periode berlangganan akan dimulai dari hari ini.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Referensi Pembayaran (opsional)</label>
                        <input type="text" wire:model="paymentReference" class="form-control" placeholder="No. rekening pengirim / catatan">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="$set('showActivateModal', false)">Batal</button>
                    <button type="button" class="btn btn-success" wire:click="activateSubscription">
                        <i class="ri-check-double-line me-1"></i> Aktifkan Sekarang
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
