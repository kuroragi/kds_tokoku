<div>
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">
                @if($isSuperAdmin)
                <div class="col-lg-4">
                    <label class="form-label small text-muted mb-1">Unit Usaha</label>
                    <select class="form-select form-select-sm" wire:model.live="business_unit_id">
                        <option value="">-- Pilih Unit Usaha --</option>
                        @foreach($units as $unit)
                        <option value="{{ $unit->id }}">{{ $unit->code }} — {{ $unit->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
            </div>
        </div>
    </div>

    @if(count($settings) > 0)
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form wire:submit="save">
                <div class="row g-3">
                    @foreach($settings as $idx => $setting)
                    <div class="col-md-6">
                        <label class="form-label fw-medium">{{ $setting['label'] }}</label>
                        @if($setting['description'])
                        <small class="text-muted d-block mb-1">{{ $setting['description'] }}</small>
                        @endif

                        @if($setting['type'] === 'boolean')
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox"
                                wire:model="settings.{{ $idx }}.value"
                                id="setting-{{ $setting['key'] }}"
                                {{ $setting['value'] ? 'checked' : '' }}>
                            <label class="form-check-label" for="setting-{{ $setting['key'] }}">
                                {{ $setting['value'] ? 'Aktif' : 'Non-aktif' }}
                            </label>
                        </div>
                        @elseif($setting['type'] === 'percentage')
                        <div class="input-group input-group-sm">
                            <input type="number" class="form-control"
                                wire:model="settings.{{ $idx }}.value"
                                step="0.01" min="0" max="100">
                            <span class="input-group-text">%</span>
                        </div>
                        @elseif($setting['type'] === 'amount')
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">Rp</span>
                            <input type="number" class="form-control"
                                wire:model="settings.{{ $idx }}.value"
                                min="0">
                        </div>
                        @else
                        <input type="text" class="form-control form-control-sm"
                            wire:model="settings.{{ $idx }}.value">
                        @endif
                    </div>
                    @endforeach
                </div>

                <div class="mt-4 text-end">
                    <button type="submit" class="btn btn-primary btn-sm" wire:loading.attr="disabled">
                        <span wire:loading.remove><i class="ri-save-line"></i> Simpan Pengaturan</span>
                        <span wire:loading><i class="ri-loader-4-line"></i> Menyimpan...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @else
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5">
            <div class="text-muted">
                <i class="ri-settings-3-line" style="font-size: 3rem; opacity: 0.3;"></i>
                <p class="mt-2 mb-0">Pilih unit usaha untuk mengelola pengaturan payroll</p>
            </div>
        </div>
    </div>
    @endif
</div>
