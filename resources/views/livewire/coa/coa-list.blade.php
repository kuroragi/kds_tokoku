<div class="card-body p-0">
    <!-- COA List Component -->
    <div>
        <!-- Search and Filter Controls -->
        <div class="row mb-3">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="ri-search-line"></i>
                    </span>
                    <input type="text" class="form-control" placeholder="Search by code or name..."
                        wire:model.live.debounce.300ms="search">
                </div>
            </div>
            <div class="col-md-3">
                <select class="form-select" wire:model.live="filterType">
                    <option value="">All Types</option>
                    @foreach($types as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" wire:model.live="filterStatus">
                    <option value="">All Status</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            <div class="col-md-2 text-end">
                <button class="btn btn-primary w-100" wire:click="$dispatch('openCoaModal')">
                    <i class="ri-add-line"></i> Add Account
                </button>
            </div>
        </div>

        <!-- COA Table -->
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th scope="col" width="5%">
                            <input type="checkbox" class="form-check-input" id="selectAll">
                        </th>
                        <th scope="col" width="30%">
                            <button type="button" class="btn btn-link text-white p-0 text-start text-decoration-none"
                                wire:click="sortBy('name')">
                                Account Name
                                @if ($sortField === 'name')
                                <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-line"></i>
                                @endif
                            </button>
                        </th>
                        <th scope="col" width="10%">
                            <button type="button" class="btn btn-link text-white p-0 text-start text-decoration-none"
                                wire:click="sortBy('type')">
                                Saldo Normal
                                @if ($sortField === 'type')
                                <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-line"></i>
                                @endif
                            </button>
                        </th>
                        <th scope="col" width="10%" class="text-center">Status</th>
                        <th scope="col" width="15%" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($coas as $coa)
                    <tr wire:key="coa-{{ $coa->id }}">
                        <td>
                            <input type="checkbox" class="form-check-input" value="{{ $coa->id }}">
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                @if($coa->level > 0)
                                <span class="text-muted @if($coa->level > 1) me-2 @endif">{{ str_repeat('→
                                    ', ($coa->level - 1) * 2)
                                    }}</span>
                                @endif
                                <div>
                                    <strong class="text-primary">{{ $coa->code }}</strong> - <strong>{{
                                        $coa->name
                                        }}</strong>
                                    @if($coa->parent)
                                    <br>
                                    <small class="text-muted">
                                        <i class="ri-arrow-right-line"></i>
                                        Parent: {{ $coa->parent->name }}
                                    </small>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            @php
                            // Determine saldo normal based on account type
                            $saldoNormal = 'D'; // Default Debit
                            if (in_array($coa->type, ['pasiva', 'modal', 'pendapatan'])) {
                            $saldoNormal = 'K'; // Kredit
                            }
                            $badgeColor = $saldoNormal === 'D' ? 'primary' : 'danger';
                            @endphp
                            <span class="badge bg-{{ $badgeColor }}">
                                {{ $saldoNormal }}
                            </span>
                        </td>
                        <td class="text-center">
                            <div class="form-check form-switch d-flex justify-content-center">
                                <input class="form-check-input" type="checkbox" role="switch" {{ $coa->is_active
                                ? 'checked'
                                : '' }}
                                wire:click="toggleStatus({{ $coa->id }})"
                                style="cursor: pointer;">
                            </div>
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-outline-primary"
                                    wire:click="$dispatch('editCoa', { coaId: {{ $coa->id }} })" title="Edit Account">
                                    <i class="ri-edit-line"></i>
                                </button>
                                <button type="button" class="btn btn-outline-danger"
                                    onclick="confirmDeleteCoa('{{ $coa->name }}', {{ $coa->id }})"
                                    title="Delete Account">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4">
                            <div class="d-flex flex-column align-items-center">
                                <i class="ri-file-list-line display-4 text-muted mb-3"></i>
                                <h5 class="text-muted">No Chart of Accounts found</h5>
                                <p class="text-muted mb-0">
                                    @if ($search || $filterType || $filterStatus !== '')
                                    Try adjusting your search or filter criteria
                                    @else
                                    Create your first account to get started
                                    @endif
                                </p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>