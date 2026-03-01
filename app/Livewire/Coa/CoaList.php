<?php

namespace App\Livewire\Coa;

use App\Models\COA;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Kuroragi\GeneralHelper\ActivityLog\ActivityLogger;
use Livewire\Component;

class CoaList extends Component
{
    // Search and Filter Properties
    public $search = '';
    public $filterType = '';
    public $filterStatus = '';
    public $sortField = 'code';
    public $sortDirection = 'asc';

    // Listeners
    protected $listeners = [
        'refreshCoaList' => '$refresh',
        'coaDeleted' => '$refresh'
    ];

    /**
     * Sort by field
     */
    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    /**
     * Resolve the current user's business unit id.
     */
    protected function getBusinessUnitId(): ?int
    {
        return Auth::user()?->business_unit_id;
    }

    /**
     * Get COAs with search and filters - scoped to current business unit.
     */
    public function getCoasProperty()
    {
        $businessUnitId = $this->getBusinessUnitId();

        $query = COA::with('parent')
            ->when($businessUnitId, fn($q) => $q->where('business_unit_id', $businessUnitId))
            ->when(!$businessUnitId, fn($q) => $q->whereNull('business_unit_id'));

        // Apply search
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('code', 'like', "%{$this->search}%")
                  ->orWhere('name', 'like', "%{$this->search}%");
            });
        }

        // Apply type filter
        if ($this->filterType) {
            $query->where('type', $this->filterType);
        }

        // Apply status filter
        if ($this->filterStatus !== '') {
            $query->where('is_active', $this->filterStatus);
        }

        // Apply sorting
        $query->orderBy($this->sortField, $this->sortDirection);

        // Return all data without pagination
        return $query->get();
    }

    /**
     * Delete COA with transaction and activity logging
     */
    public function deleteCoa($coaId)
    {
        DB::beginTransaction();
        
        try {
            $coa = COA::findOrFail($coaId);
            
            // Check if COA has children
            $childrenCount = $coa->children()->count();
            
            if ($childrenCount > 0) {
                $this->dispatch('showAlert', [
                    'type' => 'error',
                    'message' => "Cannot delete COA '{$coa->name}' because it has {$childrenCount} sub-account(s). Please remove sub-accounts first."
                ]);
                
                DB::rollBack();
                return;
            }

            // Check if COA has journal entries
            $journalCount = $coa->journal_details()->count();
            
            if ($journalCount > 0) {
                $this->dispatch('showAlert', [
                    'type' => 'error',
                    'message' => "Cannot delete COA '{$coa->name}' because it has {$journalCount} journal transaction(s). Please remove transactions first."
                ]);
                
                DB::rollBack();
                return;
            }

            $coaName = $coa->name;
            $coaCode = $coa->code;
            $coa->delete();

            // Log the activity
            $logger = new ActivityLogger(storage_path('logs/activity'));
            $logger->log([
                'level' => 'info',
                'category' => 'coa_management',
                'message' => 'Chart of Account deleted successfully',
                'meta' => [
                    'action' => 'coa_deleted',
                    'coa_id' => $coaId,
                    'coa_code' => $coaCode,
                    'coa_name' => $coaName,
                    'user_id' => Auth::id(),
                    'user_name' => Auth::user()->name
                ]
            ]);

            DB::commit();

            $this->dispatch('showAlert', [
                'type' => 'success',
                'message' => "Chart of Account '{$coaName}' has been deleted successfully."
            ]);

            $this->dispatch('coaDeleted');

        } catch (\Exception $e) {
            DB::rollBack();
            
            $this->dispatch('showAlert', [
                'type' => 'error',
                'message' => 'Failed to delete Chart of Account: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Toggle COA status
     */
    public function toggleStatus($coaId)
    {
        DB::beginTransaction();
        
        try {
            $coa = COA::findOrFail($coaId);
            $oldStatus = $coa->is_active;
            $coa->is_active = !$coa->is_active;
            $coa->save();

            // Log the activity
            $logger = new ActivityLogger(storage_path('logs/activity'));
            $logger->log([
                'level' => 'info',
                'category' => 'coa_management',
                'message' => 'Chart of Account status changed',
                'meta' => [
                    'action' => 'coa_status_changed',
                    'coa_id' => $coaId,
                    'coa_code' => $coa->code,
                    'coa_name' => $coa->name,
                    'old_status' => $oldStatus,
                    'new_status' => $coa->is_active,
                    'user_id' => Auth::id(),
                    'user_name' => Auth::user()->name
                ]
            ]);

            DB::commit();

            $this->dispatch('showAlert', [
                'type' => 'success',
                'message' => "Status for '{$coa->name}' has been " . ($coa->is_active ? 'activated' : 'deactivated') . "."
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            $this->dispatch('showAlert', [
                'type' => 'error',
                'message' => 'Failed to change status: ' . $e->getMessage()
            ]);
        }
    }

    public function render()
    {
        return view('livewire.coa.coa-list', [
            'coas' => $this->coas,
            'types' => [
                'aktiva' => 'Aktiva',
                'pasiva' => 'Pasiva',
                'modal' => 'Modal',
                'pendapatan' => 'Pendapatan',
                'beban' => 'Beban'
            ]
        ]);
    }
}
