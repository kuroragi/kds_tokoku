<div>
    {{-- Header --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">
                <div class="col-lg-6">
                    <label class="form-label small text-muted mb-1">Cari Role</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="ri-search-line"></i></span>
                        <input type="text" class="form-control" wire:model.live.debounce.300ms="search"
                            placeholder="Nama role...">
                    </div>
                </div>
                <div class="col-lg-6 text-end">
                    <button class="btn btn-primary btn-sm" wire:click="openModal">
                        <i class="ri-add-line"></i> Tambah Role
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
                        <th width="20%">Nama Role</th>
                        <th width="45%">Permissions</th>
                        <th width="10%" class="text-center">Users</th>
                        <th width="10%" class="text-center">Guard</th>
                        <th width="10%" class="text-center pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($roles as $idx => $role)
                    <tr wire:key="role-{{ $role->id }}">
                        <td class="ps-3 text-muted">{{ $idx + 1 }}</td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="bg-{{ $role->name === 'superadmin' ? 'danger' : 'primary' }} bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                    <i class="ri-shield-user-line text-{{ $role->name === 'superadmin' ? 'danger' : 'primary' }}"></i>
                                </div>
                                <div>
                                    <span class="fw-medium">{{ ucfirst($role->name) }}</span>
                                    @if($role->name === 'superadmin')
                                        <i class="ri-lock-line text-danger ms-1" title="Protected"></i>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($role->permissions->count())
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($role->permissions->take(5) as $perm)
                                    <span class="badge bg-light text-dark border">{{ $perm->name }}</span>
                                    @endforeach
                                    @if($role->permissions->count() > 5)
                                    <span class="badge bg-secondary">+{{ $role->permissions->count() - 5 }} lainnya</span>
                                    @endif
                                </div>
                            @else
                                <span class="text-muted small fst-italic">Belum ada permission</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="badge bg-info bg-opacity-75">{{ $role->users_count }}</span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border font-monospace">{{ $role->guard_name }}</span>
                        </td>
                        <td class="text-center pe-3">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" wire:click="editRole({{ $role->id }})" title="Edit">
                                    <i class="ri-pencil-line"></i>
                                </button>
                                @if($role->name !== 'superadmin')
                                <button class="btn btn-outline-danger"
                                    onclick="confirmDelete(() => @this.deleteRole({{ $role->id }}))"
                                    title="Hapus">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <div class="text-muted">
                                <i class="ri-shield-line" style="font-size: 3rem; opacity: 0.3;"></i>
                                <p class="mt-2 mb-0">Belum ada role</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white py-2">
            <small class="text-muted">Total: {{ $roles->count() }} role</small>
        </div>
    </div>

    {{-- Modal Form --}}
    @if($showModal)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white py-2">
                    <h6 class="modal-title">
                        <i class="ri-{{ $isEditing ? 'pencil' : 'add' }}-line me-1"></i>
                        {{ $isEditing ? 'Edit Role' : 'Tambah Role Baru' }}
                    </h6>
                    <button type="button" class="btn-close btn-close-white btn-sm" wire:click="closeModal"></button>
                </div>
                <form wire:submit="save">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Role <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('roleName') is-invalid @enderror"
                                wire:model="roleName" placeholder="contoh: admin, kasir, pemilik">
                            @error('roleName') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Permissions Checkboxes --}}
                        <div>
                            <label class="form-label">Permissions</label>
                            @if($permissions->count())
                            @php
                                $grouped = $permissions->groupBy(function ($p) {
                                    $parts = explode('.', $p->name);
                                    return $parts[0] ?? 'general';
                                });
                            @endphp
                            <div class="row g-2">
                                @foreach($grouped as $module => $perms)
                                <div class="col-md-6">
                                    <div class="card border">
                                        <div class="card-header bg-light py-1 px-2">
                                            <small class="fw-bold text-uppercase text-muted">{{ $module }}</small>
                                        </div>
                                        <div class="card-body py-2 px-2">
                                            @foreach($perms as $perm)
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox"
                                                    id="perm_{{ $perm->id }}"
                                                    value="{{ $perm->name }}"
                                                    wire:model="selectedPermissions">
                                                <label class="form-check-label small" for="perm_{{ $perm->id }}">
                                                    {{ $perm->name }}
                                                </label>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            @else
                            <p class="text-muted small fst-italic mb-0">
                                Belum ada permission. Buat permission terlebih dahulu.
                            </p>
                            @endif
                        </div>
                    </div>
                    <div class="modal-footer bg-light py-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="closeModal">
                            <i class="ri-close-line"></i> Batal
                        </button>
                        <button type="submit" class="btn btn-primary btn-sm" wire:loading.attr="disabled">
                            <span wire:loading.remove><i class="ri-save-line"></i> Simpan</span>
                            <span wire:loading><i class="ri-loader-4-line"></i> Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
