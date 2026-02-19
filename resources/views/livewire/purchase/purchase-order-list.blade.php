<div>
    {{-- Header & Filters --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">
                <div class="col-lg-3">
                    <label class="form-label small text-muted mb-1">Cari PO</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="ri-search-line"></i></span>
                        <input type="text" class="form-control" wire:model.live.debounce.300ms="search"
                            placeholder="No. PO, vendor, catatan...">
                    </div>
                </div>
                @if($isSuperAdmin)
                <div class="col-lg-2">
                    <label class="form-label small text-muted mb-1">Unit Usaha</label>
                    <select class="form-select form-select-sm" wire:model.live="filterUnit">
                        <option value="">Semua Unit</option>
                        @foreach($units as $unit)
                        <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-lg-2">
                    <label class="form-label small text-muted mb-1">Status</label>
                    <select class="form-select form-select-sm" wire:model.live="filterStatus">
                        <option value="">Semua Status</option>
                        @foreach($statuses as $val => $label)
                        <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-5 text-end">
                    <button class="btn btn-primary btn-sm" wire:click="$dispatch('openPurchaseOrderModal')">
                        <i class="ri-add-line"></i> Buat PO Baru
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="5%" class="ps-3">#</th>
                        <th width="14%" style="cursor:pointer" wire:click="sortBy('po_number')">
                            No. PO
                            @if($sortField === 'po_number')
                            <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>
                            @endif
                        </th>
                        <th width="12%" style="cursor:pointer" wire:click="sortBy('po_date')">
                            Tanggal
                            @if($sortField === 'po_date')
                            <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>
                            @endif
                        </th>
                        <th width="18%">Vendor</th>
                        <th width="14%" class="text-end">Grand Total</th>
                        <th width="12%">Status</th>
                        <th width="10%">Tgl. Diharapkan</th>
                        <th width="15%" class="text-center pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($purchaseOrders as $idx => $po)
                    <tr wire:key="po-{{ $po->id }}">
                        <td class="ps-3 text-muted">{{ $idx + 1 }}</td>
                        <td class="fw-semibold">{{ $po->po_number }}</td>
                        <td>{{ $po->po_date->format('d/m/Y') }}</td>
                        <td>{{ $po->vendor->name }}</td>
                        <td class="text-end fw-semibold">Rp {{ number_format($po->grand_total, 0, ',', '.') }}</td>
                        <td>
                            @php
                                $statusColors = ['draft' => 'secondary', 'confirmed' => 'primary', 'partial_received' => 'warning', 'received' => 'success', 'cancelled' => 'danger'];
                            @endphp
                            <span class="badge bg-{{ $statusColors[$po->status] ?? 'secondary' }}">{{ $statuses[$po->status] ?? $po->status }}</span>
                        </td>
                        <td class="text-muted small">{{ $po->expected_date?->format('d/m/Y') ?? '-' }}</td>
                        <td class="text-center pe-3">
                            <div class="btn-group btn-group-sm">
                                @if($po->status === 'draft')
                                <button class="btn btn-outline-primary btn-sm" wire:click="$dispatch('editPurchaseOrder', { id: {{ $po->id }} })" title="Edit">
                                    <i class="ri-pencil-line"></i>
                                </button>
                                <button class="btn btn-outline-success btn-sm" wire:click="confirmPO({{ $po->id }})" wire:confirm="Konfirmasi PO ini?" title="Konfirmasi">
                                    <i class="ri-check-line"></i>
                                </button>
                                <button class="btn btn-outline-danger btn-sm" wire:click="deletePO({{ $po->id }})" wire:confirm="Hapus PO ini?" title="Hapus">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                                @endif
                                @if(in_array($po->status, ['confirmed', 'partial_received']))
                                <button class="btn btn-outline-success btn-sm" wire:click="$dispatch('openPurchaseFromPO', { poId: {{ $po->id }} })" title="Terima Barang">
                                    <i class="ri-truck-line"></i>
                                </button>
                                <button class="btn btn-outline-danger btn-sm" wire:click="cancelPO({{ $po->id }})" wire:confirm="Batalkan PO ini?" title="Batal">
                                    <i class="ri-close-line"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="ri-file-list-3-line fs-3 d-block mb-2"></i>
                            Belum ada Purchase Order.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
