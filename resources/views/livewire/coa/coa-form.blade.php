<!-- COA Form Modal -->
<div>
    @if ($showModal)
    <div class="modal fade show" style="display: block;" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="ri-file-list-line"></i>
                        {{ $isEditing ? 'Edit Chart of Account' : 'Add New Chart of Account' }}
                    </h5>
                    <button type="button" class="btn-close" wire:click="closeModal"></button>
                </div>

                    <form wire:submit="save">
                    <div class="modal-body">
                        <div class="row">
                            <!-- Account Code -->
                            <div class="col-md-6 mb-3">
                                <label for="code" class="form-label">
                                    Account Code <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control @error('code') is-invalid @enderror" id="code"
                                    wire:model.live="code" placeholder="e.g., 1-1101" required>
                                @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Unique identifier for the account</small>
                            </div>

                            <!-- Account Name -->
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">
                                    Account Name <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                                    wire:model.live="name" placeholder="e.g., Cash in Bank" required>
                                @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <!-- Account Type -->
                            <div class="col-md-6 mb-3">
                                <label for="type" class="form-label">
                                    Account Type <span class="text-danger">*</span>
                                </label>
                                <select class="form-select @error('type') is-invalid @enderror" id="type"
                                    wire:model.live="type" required>
                                    @foreach($types as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Classification of the account</small>
                            </div>

                            <!-- Parent Account -->
                            <div class="col-md-6 mb-3">
                                <label for="parent_code" class="form-label">
                                    Parent Account
                                </label>
                                <select class="form-select @error('parent_code') is-invalid @enderror" id="parent_code"
                                    wire:model.live="parent_code">
                                    <option value="">-- No Parent (Root Account) --</option>
                                    @foreach($parentOptions as $option)
                                    <option value="{{ $option->id }}">
                                        {{ $option->code }} - {{ $option->name }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('parent_code')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Optional: Create hierarchy</small>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Order -->
                            <div class="col-md-4 mb-3">
                                <label for="order" class="form-label">
                                    Display Order <span class="text-danger">*</span>
                                </label>
                                <select class="form-select @error('order') is-invalid @enderror" id="order" wire:model.live="order" min="1" required>
                                    <option value="">Pilih Urutan</option>
                                    @foreach ($orderOptions as $key => $option)
                                        <option value="{{ $key }}">{{ $option }}</option>
                                    @endforeach
                                </select>
                                @error('order')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Sorting sequence</small>
                            </div>

                            <!-- Level (Read-only) -->
                            <div class="col-md-4 mb-3">
                                <label for="level" class="form-label">
                                    Hierarchy Level
                                </label>
                                <input type="number" class="form-control" id="level" value="{{ $level }}" disabled>
                                <small class="text-muted">Auto-calculated</small>
                            </div>

                            <!-- Leaf Account Status -->
                            <div class="col-md-4 mb-3">
                                <label for="is_leaf_account" class="form-label">
                                    Leaf Account
                                </label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" role="switch" id="is_leaf_account"
                                        wire:model.live="is_leaf_account">
                                    <label class="form-check-label" for="is_leaf_account">
                                        {{ $is_leaf_account ? 'Ya (Akun Transaksi)' : 'Tidak (Akun Pengelompokan)' }}
                                    </label>
                                </div>
                                <small class="text-muted">
                                    <i class="ri-information-line"></i>
                                    {{ $is_leaf_account ? 'Akun ini bisa digunakan untuk transaksi jurnal' : 'Akun ini hanya untuk pengelompokan (parent)' }}
                                </small>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Active Status -->
                            <div class="col-md-12 mb-3">
                                <label for="is_active" class="form-label">
                                    Status
                                </label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" role="switch" id="is_active"
                                        wire:model="is_active">
                                    <label class="form-check-label" for="is_active">
                                        {{ $is_active ? 'Active' : 'Inactive' }}
                                    </label>
                                </div>
                                <small class="text-muted">Enable/disable account</small>
                            </div>
                        </div>

                        <!-- Preview Section -->
                        @if ($code || $name)
                        <div class="alert alert-info mt-3">
                            <h6 class="alert-heading">
                                <i class="ri-eye-line"></i> Account Preview
                            </h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Code:</strong> <code>{{ $code }}</code><br>
                                    <strong>Name:</strong> {{ $name }}<br>
                                    <strong>Type:</strong>
                                    <span class="badge bg-primary">{{ $types[$type] ?? $type }}</span>
                                </div>
                                <div class="col-md-6">
                                    <strong>Level:</strong> {{ $level }}<br>
                                    <strong>Order:</strong> {{ $order }}<br>
                                    <strong>Account Type:</strong>
                                    <span class="badge bg-{{ $is_leaf_account ? 'success' : 'info' }}">
                                        {{ $is_leaf_account ? 'Leaf Account' : 'Parent Account' }}
                                    </span><br>
                                    <strong>Status:</strong>
                                    <span class="badge bg-{{ $is_active ? 'success' : 'secondary' }}">
                                        {{ $is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Description -->
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control @error('description') is-invalid @enderror" id="description"
                                    wire:model.blur="description" rows="3" placeholder="Optional account description" maxlength="500"></textarea>
                                @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Max 500 characters</small>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeModal">
                            <i class="ri-close-line"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                            <span wire:loading.remove>
                                <i class="ri-save-line"></i>
                                {{ $isEditing ? 'Update Account' : 'Create Account' }}
                            </span>
                            <span wire:loading>
                                <i class="ri-loader-4-line spin"></i> Saving...
                            </span>
                        </button>
                    </div>
                    </form>
            </div>
        </div>
    </div>

    <!-- Modal Backdrop -->
    <div class="modal-backdrop fade show"></div>
    @endif
</div>
<!-- Custom CSS -->
@push('style')
<style>
    .spin {
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        from {
            transform: rotate(0deg);
        }

        to {
            transform: rotate(360deg);
        }
    }
</style>
@endpush