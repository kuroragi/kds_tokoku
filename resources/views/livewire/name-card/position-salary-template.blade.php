<div>
    @if($showModal)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white py-2">
                    <h6 class="modal-title">
                        <i class="ri-price-tag-3-line me-1"></i>
                        Template Gaji — {{ $positionName }}
                    </h6>
                    <button type="button" class="btn-close btn-close-white btn-sm" wire:click="closeModal"></button>
                </div>
                <div class="modal-body">
                    {{-- Current assignments --}}
                    <table class="table table-sm align-middle mb-3">
                        <thead class="table-light">
                            <tr>
                                <th>Komponen</th>
                                <th width="35%">Nominal (Rp)</th>
                                <th width="10%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($assignments as $assign)
                            <tr wire:key="psc-{{ $assign['id'] }}">
                                <td>
                                    <code class="text-muted">{{ $assign['component_code'] }}</code>
                                    — {{ $assign['component_name'] }}
                                </td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control"
                                            value="{{ $assign['amount'] }}"
                                            wire:change="updateAmount({{ $assign['id'] }}, $event.target.value)"
                                            min="0">
                                    </div>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-outline-danger btn-sm"
                                        wire:click="removeComponent({{ $assign['id'] }})"
                                        wire:confirm="Hapus komponen ini dari template?">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-3">
                                    Belum ada komponen gaji pada template ini
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>

                    {{-- Add new --}}
                    @if(count($availableComponents) > 0)
                    <div class="border-top pt-3">
                        <h6 class="small text-muted mb-2">Tambah Komponen</h6>
                        <div class="row g-2">
                            <div class="col-md-5">
                                <select class="form-select form-select-sm" wire:model="newComponentId">
                                    <option value="">-- Pilih Komponen --</option>
                                    @foreach($availableComponents as $comp)
                                    <option value="{{ $comp['id'] }}">{{ $comp['code'] }} — {{ $comp['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" wire:model="newAmount" placeholder="Nominal" min="0">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-primary btn-sm w-100" wire:click="addComponent">
                                    <i class="ri-add-line"></i> Tambah
                                </button>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="closeModal">
                        <i class="ri-close-line"></i> Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
