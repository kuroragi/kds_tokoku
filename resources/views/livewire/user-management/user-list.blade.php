<div>
    {{-- Header & Filters --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">
                <div class="col-lg-3">
                    <label class="form-label small text-muted mb-1">Cari User</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="ri-search-line"></i></span>
                        <input type="text" class="form-control" wire:model.live.debounce.300ms="search"
                            placeholder="Nama, username, email...">
                    </div>
                </div>
                <div class="col-lg-2">
                    <label class="form-label small text-muted mb-1">Role</label>
                    <select class="form-select form-select-sm" wire:model.live="filterRole">
                        <option value="">Semua Role</option>
                        @foreach($roles as $role)
                        <option value="{{ $role->name }}">{{ ucfirst($role->name) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2">
                    <label class="form-label small text-muted mb-1">Unit Usaha</label>
                    <select class="form-select form-select-sm" wire:model.live="filterUnit">
                        <option value="">Semua Unit</option>
                        @foreach($units as $unit)
                        <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2">
                    <label class="form-label small text-muted mb-1">Status</label>
                    <select class="form-select form-select-sm" wire:model.live="filterStatus">
                        <option value="">Semua Status</option>
                        <option value="1">Aktif</option>
                        <option value="0">Non-aktif</option>
                    </select>
                </div>
                <div class="col-lg-3 text-end">
                    <button class="btn btn-primary btn-sm" wire:click="$dispatch('openUserModal')">
                        <i class="ri-user-add-line"></i> Tambah User
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
                        <th width="22%">Nama</th>
                        <th width="15%">Username</th>
                        <th width="18%">Email</th>
                        <th width="12%">Role</th>
                        <th width="12%">Unit Usaha</th>
                        <th width="8%" class="text-center">Status</th>
                        <th width="8%" class="text-center pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $idx => $user)
                    <tr wire:key="user-{{ $user->id }}">
                        <td class="ps-3 text-muted">{{ $idx + 1 }}</td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 36px; height: 36px;">
                                    <i class="ri-user-line text-primary"></i>
                                </div>
                                <div>
                                    <div class="fw-medium">{{ $user->name }}</div>
                                </div>
                            </div>
                        </td>
                        <td><code class="text-muted">{{ $user->username }}</code></td>
                        <td class="text-muted small">{{ $user->email }}</td>
                        <td>
                            @foreach($user->roles as $role)
                            <span class="badge {{ $role->name === 'superadmin' ? 'bg-danger' : 'bg-primary' }} bg-opacity-75">
                                {{ ucfirst($role->name) }}
                            </span>
                            @endforeach
                        </td>
                        <td>
                            @if($user->businessUnit)
                                <span class="badge bg-info bg-opacity-75">{{ $user->businessUnit->name }}</span>
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="form-check form-switch d-inline-block">
                                <input class="form-check-input" type="checkbox" {{ $user->is_active ? 'checked' : '' }}
                                    wire:click="toggleStatus({{ $user->id }})"
                                    @if($user->hasRole('superadmin')) disabled title="Super Admin tidak bisa dinonaktifkan" @endif>
                            </div>
                        </td>
                        <td class="text-center pe-3">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" wire:click="$dispatch('editUser', { id: {{ $user->id }} })" title="Edit">
                                    <i class="ri-pencil-line"></i>
                                </button>
                                @unless($user->hasRole('superadmin') || $user->id === auth()->id())
                                <button class="btn btn-outline-danger"
                                    onclick="confirmDelete(() => @this.deleteUser({{ $user->id }}))"
                                    title="Hapus">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                                @endunless
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <div class="text-muted">
                                <i class="ri-user-line" style="font-size: 3rem; opacity: 0.3;"></i>
                                <p class="mt-2 mb-0">Belum ada user yang terdaftar</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white py-2">
            <small class="text-muted">Total: {{ $users->count() }} user</small>
        </div>
    </div>
</div>
