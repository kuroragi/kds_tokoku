<div>
    {{-- Header --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">
                <div class="col-lg-6">
                    <label class="form-label small text-muted mb-1">Cari Permission</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="ri-search-line"></i></span>
                        <input type="text" class="form-control" wire:model.live.debounce.300ms="search"
                            placeholder="Nama permission...">
                    </div>
                </div>
                <div class="col-lg-6 text-end">
                    <button class="btn btn-primary btn-sm" wire:click="openModal">
                        <i class="ri-add-line"></i> Tambah Permission
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Grouped Permissions --}}
    @if($groupedPermissions->count())
        @foreach($groupedPermissions as $module => $perms)
        <div class="card border-0 shadow-sm mb-3" wire:key="group-{{ $module }}">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                <h6 class="mb-0">
                    <i class="ri-folder-line text-primary me-1"></i>
                    <span class="text-uppercase">{{ $module }}</span>
                    <span class="badge bg-primary bg-opacity-75 ms-2">{{ $perms->count() }}</span>
                </h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="5%" class="ps-3">#</th>
                            <th width="40%">Nama Permission</th>
                            <th width="15%" class="text-center">Roles</th>
                            <th width="15%" class="text-center">Guard</th>
                            <th width="10%" class="text-center pe-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($perms as $idx => $permission)
                        <tr wire:key="perm-{{ $permission->id }}">
                            <td class="ps-3 text-muted">{{ $idx + 1 }}</td>
                            <td>
                                <span class="font-monospace">{{ $permission->name }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-info bg-opacity-75">{{ $permission->roles_count }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border font-monospace">{{ $permission->guard_name }}</span>
                            </td>
                            <td class="text-center pe-3">
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary" wire:click="editPermission({{ $permission->id }})" title="Edit">
                                        <i class="ri-pencil-line"></i>
                                    </button>
                                    @if($permission->roles_count === 0)
                                    <button class="btn btn-outline-danger"
                                        onclick="confirmDelete(() => @this.deletePermission({{ $permission->id }}))"
                                        title="Hapus">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endforeach
    @else
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <div class="text-muted">
                    <i class="ri-key-line" style="font-size: 3rem; opacity: 0.3;"></i>
                    <p class="mt-2 mb-0">Belum ada permission</p>
                    <p class="small">Gunakan format <code>modul.aksi</code> (contoh: <code>user.create</code>)</p>
                </div>
            </div>
        </div>
    @endif

    <div class="mt-2">
        <small class="text-muted">Total: {{ $permissions->count() }} permission</small>
    </div>

    {{-- Modal Form --}}
    @if($showModal)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white py-2">
                    <h6 class="modal-title">
                        <i class="ri-{{ $isEditing ? 'pencil' : 'add' }}-line me-1"></i>
                        {{ $isEditing ? 'Edit Permission' : 'Tambah Permission Baru' }}
                    </h6>
                    <button type="button" class="btn-close btn-close-white btn-sm" wire:click="closeModal"></button>
                </div>
                <form wire:submit="save">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Permission <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('permissionName') is-invalid @enderror"
                                wire:model="permissionName" placeholder="contoh: user.create, journal.view">
                            @error('permissionName') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">
                                Gunakan format <code>modul.aksi</code> untuk pengelompokan otomatis.
                            </div>
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
