<div>
    {{-- Summary Cards --}}
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card border-0 bg-primary bg-opacity-10">
                <div class="card-body py-3 text-center">
                    <div class="text-muted small">Total Voucher</div>
                    <div class="fw-bold fs-4 text-primary">{{ $this->summary['total'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 bg-success bg-opacity-10">
                <div class="card-body py-3 text-center">
                    <div class="text-muted small">Aktif</div>
                    <div class="fw-bold fs-4 text-success">{{ $this->summary['active'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 bg-warning bg-opacity-10">
                <div class="card-body py-3 text-center">
                    <div class="text-muted small">Habis Digunakan</div>
                    <div class="fw-bold fs-4 text-warning">{{ $this->summary['used_up'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 bg-danger bg-opacity-10">
                <div class="card-body py-3 text-center">
                    <div class="text-muted small">Kadaluarsa</div>
                    <div class="fw-bold fs-4 text-danger">{{ $this->summary['expired'] }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">
                <div class="col-lg-3">
                    <label class="form-label small text-muted mb-1">Cari</label>
                    <input type="text" class="form-control form-control-sm" wire:model.live.debounce.300ms="search"
                        placeholder="Kode voucher, deskripsi...">
                </div>
                <div class="col-lg-2">
                    <label class="form-label small text-muted mb-1">Paket</label>
                    <select class="form-select form-select-sm" wire:model.live="filterPlan">
                        <option value="">Semua</option>
                        @foreach($this->plans as $plan)
                        <option value="{{ $plan->id }}">{{ $plan->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2">
                    <label class="form-label small text-muted mb-1">Tipe</label>
                    <select class="form-select form-select-sm" wire:model.live="filterType">
                        <option value="">Semua</option>
                        <option value="promo">Promo</option>
                        <option value="testing">Testing</option>
                        <option value="owner">Owner</option>
                    </select>
                </div>
                <div class="col-lg-2">
                    <label class="form-label small text-muted mb-1">Status</label>
                    <select class="form-select form-select-sm" wire:model.live="filterStatus">
                        <option value="">Semua</option>
                        <option value="active">Aktif</option>
                        <option value="used_up">Habis</option>
                        <option value="expired">Kadaluarsa</option>
                        <option value="inactive">Nonaktif</option>
                    </select>
                </div>
                <div class="col-lg-3 text-end">
                    <button class="btn btn-primary btn-sm" wire:click="openCreate">
                        <i class="ri-add-line"></i> Generate Voucher
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="4%">#</th>
                        <th width="18%">Kode</th>
                        <th width="12%">Paket</th>
                        <th width="8%">Tipe</th>
                        <th width="8%">Durasi</th>
                        <th width="10%">Penggunaan</th>
                        <th width="12%">Berlaku s/d</th>
                        <th width="8%" class="text-center">Status</th>
                        <th width="20%" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($this->vouchers as $idx => $v)
                    <tr wire:key="voucher-{{ $v->id }}">
                        <td class="text-muted ps-3">{{ $this->vouchers->firstItem() + $idx }}</td>
                        <td>
                            <code class="fw-bold text-primary" style="font-size: 0.85rem; letter-spacing: 1px;">{{ $v->code }}</code>
                            @if($v->description)
                            <br><small class="text-muted">{{ Str::limit($v->description, 40) }}</small>
                            @endif
                        </td>
                        <td>
                            @php
                                $planColors = ['Trial' => 'secondary', 'Basic' => 'info', 'Medium' => 'warning', 'Premium' => 'success'];
                            @endphp
                            <span class="badge bg-{{ $planColors[$v->plan?->name] ?? 'primary' }}">{{ $v->plan?->name ?? '-' }}</span>
                        </td>
                        <td>
                            @php
                                $typeColors = ['promo' => 'primary', 'testing' => 'info', 'owner' => 'dark'];
                                $typeLabels = ['promo' => 'Promo', 'testing' => 'Testing', 'owner' => 'Owner'];
                            @endphp
                            <span class="badge bg-{{ $typeColors[$v->type] ?? 'secondary' }} bg-opacity-75">{{ $typeLabels[$v->type] ?? $v->type }}</span>
                        </td>
                        <td class="small">{{ $v->duration_days }} hari</td>
                        <td>
                            <div class="d-flex align-items-center gap-1">
                                <div class="progress flex-grow-1" style="height: 5px;">
                                    @php $usagePct = $v->max_uses > 0 ? min(($v->used_count / $v->max_uses) * 100, 100) : 0; @endphp
                                    <div class="progress-bar {{ $usagePct >= 100 ? 'bg-danger' : 'bg-success' }}" style="width: {{ $usagePct }}%"></div>
                                </div>
                                <small class="text-muted">{{ $v->used_count }}/{{ $v->max_uses }}</small>
                            </div>
                        </td>
                        <td class="small {{ $v->valid_until->lt(now()) ? 'text-danger' : '' }}">
                            {{ $v->valid_until->format('d/m/Y') }}
                        </td>
                        <td class="text-center">
                            @if(!$v->is_active)
                            <span class="badge bg-secondary"><i class="ri-close-circle-line"></i> Nonaktif</span>
                            @elseif($v->isFullyRedeemed())
                            <span class="badge bg-warning text-dark"><i class="ri-checkbox-circle-line"></i> Habis</span>
                            @elseif($v->valid_until->lt(now()))
                            <span class="badge bg-danger"><i class="ri-time-line"></i> Kadaluarsa</span>
                            @else
                            <span class="badge bg-success"><i class="ri-check-line"></i> Aktif</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" wire:click="openDetail({{ $v->id }})" title="Detail">
                                    <i class="ri-eye-line"></i>
                                </button>
                                <button class="btn btn-outline-success" wire:click="openSend({{ $v->id }})" title="Kirim ke Email">
                                    <i class="ri-mail-send-line"></i>
                                </button>
                                <button class="btn btn-outline-{{ $v->is_active ? 'warning' : 'info' }}" wire:click="toggleActive({{ $v->id }})"
                                    title="{{ $v->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                    <i class="ri-{{ $v->is_active ? 'pause-circle-line' : 'play-circle-line' }}"></i>
                                </button>
                                @if($v->used_count === 0)
                                <button class="btn btn-outline-danger" wire:click="deleteVoucher({{ $v->id }})"
                                    wire:confirm="Hapus voucher {{ $v->code }}?" title="Hapus">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="ri-coupon-line fs-3 d-block mb-2"></i>
                            Belum ada voucher. Klik "Generate Voucher" untuk membuat.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($this->vouchers->hasPages())
        <div class="card-footer bg-white border-top px-3 py-2">{{ $this->vouchers->links() }}</div>
        @endif
    </div>

    {{-- ═══════════════ GENERATE MODAL ═══════════════ --}}
    @if($showCreateModal)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5); overflow-y: auto;">
        <div class="modal-dialog modal-md" style="margin: 1.75rem auto;">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white py-2">
                    <h6 class="modal-title"><i class="ri-coupon-3-line me-1"></i> Generate Voucher</h6>
                    <button type="button" class="btn-close btn-close-white btn-sm" wire:click="closeCreate"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Paket <span class="text-danger">*</span></label>
                            <select class="form-select @error('createPlanId') is-invalid @enderror" wire:model="createPlanId">
                                <option value="">-- Pilih Paket --</option>
                                @foreach($this->plans as $plan)
                                <option value="{{ $plan->id }}">{{ $plan->name }} — Rp {{ number_format($plan->price, 0, ',', '.') }}/{{ $plan->duration_days }} hari</option>
                                @endforeach
                            </select>
                            @error('createPlanId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tipe <span class="text-danger">*</span></label>
                            <select class="form-select @error('createType') is-invalid @enderror" wire:model="createType">
                                <option value="promo">Promo</option>
                                <option value="testing">Testing</option>
                                <option value="owner">Owner</option>
                            </select>
                            @error('createType') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Durasi (hari) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('createDuration') is-invalid @enderror"
                                wire:model="createDuration" min="1" max="36500">
                            @error('createDuration') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Maks. Penggunaan <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('createMaxUses') is-invalid @enderror"
                                wire:model="createMaxUses" min="1" max="10000">
                            @error('createMaxUses') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Jumlah Voucher <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('createQuantity') is-invalid @enderror"
                                wire:model="createQuantity" min="1" max="100">
                            @error('createQuantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Deskripsi</label>
                            <input type="text" class="form-control" wire:model="createDescription"
                                placeholder="Deskripsi voucher (opsional)">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="closeCreate">Batal</button>
                    <button type="button" class="btn btn-primary btn-sm" wire:click="generateVouchers" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="generateVouchers"><i class="ri-magic-line"></i> Generate</span>
                        <span wire:loading wire:target="generateVouchers"><i class="ri-loader-4-line ri-spin"></i> Membuat...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ═══════════════ SEND EMAIL MODAL ═══════════════ --}}
    @if($showSendModal)
    @php $sendVoucher = \App\Models\Voucher::with('plan')->find($sendVoucherId); @endphp
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5); overflow-y: auto;">
        <div class="modal-dialog modal-md" style="margin: 1.75rem auto;">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-success text-white py-2">
                    <h6 class="modal-title"><i class="ri-mail-send-line me-1"></i> Kirim Voucher ke Email</h6>
                    <button type="button" class="btn-close btn-close-white btn-sm" wire:click="closeSend"></button>
                </div>
                <div class="modal-body">
                    @if($sendVoucher)
                    <div class="alert alert-light border py-2 mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <code class="fw-bold text-primary" style="font-size: 1rem; letter-spacing: 2px;">{{ $sendVoucher->code }}</code>
                                <br><small class="text-muted">{{ $sendVoucher->plan?->name }} — {{ $sendVoucher->duration_days }} hari</small>
                            </div>
                            <span class="badge bg-success fs-6">🎁</span>
                        </div>
                    </div>
                    @endif

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nama Penerima <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('sendName') is-invalid @enderror"
                                wire:model="sendName" placeholder="Nama penerima">
                            @error('sendName') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email Penerima <span class="text-danger">*</span></label>
                            <input type="email" class="form-control @error('sendEmail') is-invalid @enderror"
                                wire:model="sendEmail" placeholder="email@contoh.com">
                            @error('sendEmail') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Pesan Pribadi</label>
                            <textarea class="form-control" wire:model="sendMessage" rows="3"
                                placeholder="Tulis pesan pribadi untuk penerima... (opsional)"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="closeSend">Batal</button>
                    <button type="button" class="btn btn-success btn-sm" wire:click="sendVoucherEmail" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="sendVoucherEmail"><i class="ri-send-plane-line"></i> Kirim Email</span>
                        <span wire:loading wire:target="sendVoucherEmail"><i class="ri-loader-4-line ri-spin"></i> Mengirim...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ═══════════════ DETAIL MODAL ═══════════════ --}}
    @if($showDetailModal && $detailVoucher)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5); overflow-y: auto;">
        <div class="modal-dialog modal-lg" style="margin: 1.75rem auto;">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-info text-white py-2">
                    <h6 class="modal-title"><i class="ri-coupon-line me-1"></i> Detail Voucher</h6>
                    <button type="button" class="btn-close btn-close-white btn-sm" wire:click="closeDetail"></button>
                </div>
                <div class="modal-body">
                    {{-- Voucher Card --}}
                    <div class="text-center mb-4">
                        <div class="d-inline-block px-5 py-4 rounded-3" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <div class="text-white-50 small mb-1">KODE VOUCHER</div>
                            <div class="text-white fw-bold" style="font-size: 1.5rem; letter-spacing: 4px; font-family: 'Courier New', monospace;">
                                {{ $detailVoucher->code }}
                            </div>
                            <div class="mt-2">
                                <span class="badge bg-white text-dark px-3 py-1">
                                    {{ $detailVoucher->plan?->name }} — {{ $detailVoucher->duration_days }} hari
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Info Table --}}
                    <div class="row g-3">
                        <div class="col-md-6">
                            <table class="table table-sm mb-0">
                                <tr>
                                    <td class="text-muted" width="40%">Kode</td>
                                    <td class="fw-semibold"><code>{{ $detailVoucher->code }}</code></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Paket</td>
                                    <td>{{ $detailVoucher->plan?->name }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Tipe</td>
                                    <td>
                                        @php $typeColors = ['promo'=>'primary','testing'=>'info','owner'=>'dark']; @endphp
                                        <span class="badge bg-{{ $typeColors[$detailVoucher->type] ?? 'secondary' }}">{{ ucfirst($detailVoucher->type) }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Durasi</td>
                                    <td>{{ number_format($detailVoucher->duration_days, 0) }} hari</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Deskripsi</td>
                                    <td>{{ $detailVoucher->description ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm mb-0">
                                <tr>
                                    <td class="text-muted" width="40%">Penggunaan</td>
                                    <td><strong>{{ $detailVoucher->used_count }}</strong> / {{ $detailVoucher->max_uses }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Berlaku Dari</td>
                                    <td>{{ $detailVoucher->valid_from->format('d/m/Y') }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Berlaku Sampai</td>
                                    <td class="{{ $detailVoucher->valid_until->lt(now()) ? 'text-danger' : '' }}">
                                        {{ $detailVoucher->valid_until->format('d/m/Y') }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Status</td>
                                    <td>
                                        @if($detailVoucher->isValid())
                                        <span class="badge bg-success">Aktif</span>
                                        @elseif(!$detailVoucher->is_active)
                                        <span class="badge bg-secondary">Nonaktif</span>
                                        @elseif($detailVoucher->isFullyRedeemed())
                                        <span class="badge bg-warning text-dark">Habis</span>
                                        @else
                                        <span class="badge bg-danger">Kadaluarsa</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Dibuat</td>
                                    <td>{{ $detailVoucher->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    {{-- Redemption History --}}
                    @if($detailVoucher->redemptions->isNotEmpty())
                    <h6 class="mt-4 mb-2 border-bottom pb-2"><i class="ri-history-line me-1"></i> Riwayat Penggunaan</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">#</th>
                                    <th>User</th>
                                    <th>Email</th>
                                    <th>Tanggal Redeem</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($detailVoucher->redemptions as $idx => $r)
                                <tr>
                                    <td class="text-muted">{{ $idx + 1 }}</td>
                                    <td class="fw-semibold">{{ $r->user?->name ?? '-' }}</td>
                                    <td>{{ $r->user?->email ?? '-' }}</td>
                                    <td>{{ $r->redeemed_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
                <div class="modal-footer bg-light py-2">
                    <button class="btn btn-success btn-sm me-auto" wire:click="openSend({{ $detailVoucher->id }})">
                        <i class="ri-mail-send-line"></i> Kirim ke Email
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="closeDetail">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
