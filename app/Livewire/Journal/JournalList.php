<?php

namespace App\Livewire\Journal;

use App\Models\JournalMaster;
use App\Models\Period;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Kuroragi\GeneralHelper\ActivityLog\ActivityLogger;
use Livewire\Component;
use Livewire\WithPagination;

class JournalList extends Component
{
    use WithPagination;
    
    // Search and Filter Properties
    public $search = '';
    public $filterStatus = '';
    public $filterPeriod = '';
    public $sortField = 'journal_date';
    public $sortDirection = 'desc';
    public $perPage = 25;

    // Date range filter
    public $dateFrom = '';
    public $dateTo = '';

    // Listeners
    protected $listeners = [
        'refreshJournalList' => '$refresh',
        'journalDeleted' => '$refresh',
        'deleteJournal' => 'deleteJournal',
        'postJournal' => 'postJournal'
    ];

    /**
     * Reset pagination when search changes
     */
    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFilterStatus()
    {
        $this->resetPage();
    }

    public function updatedFilterPeriod()
    {
        $this->resetPage();
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

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
        $this->resetPage();
    }

    /**
     * Get Journals with search and filters
     */
    public function getJournalsProperty()
    {
        $query = JournalMaster::with(['period', 'journals.coa']);

        // Apply search
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('journal_no', 'like', "%{$this->search}%")
                  ->orWhere('reference', 'like', "%{$this->search}%")
                  ->orWhere('description', 'like', "%{$this->search}%");
            });
        }

        // Apply status filter
        if ($this->filterStatus !== '') {
            $query->where('status', $this->filterStatus);
        }

        // Apply period filter
        if ($this->filterPeriod) {
            $query->where('id_period', $this->filterPeriod);
        }

        // Apply date range filter
        if ($this->dateFrom) {
            $query->whereDate('journal_date', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate('journal_date', '<=', $this->dateTo);
        }

        // Apply sorting
        $query->orderBy($this->sortField, $this->sortDirection);

        return $query->paginate($this->perPage);
    }

    /**
     * Get periods for filter dropdown
     */
    public function getPeriodsProperty()
    {
        return Period::orderBy('year', 'desc')->orderBy('month', 'desc')->get();
    }

    /**
     * Delete Journal with transaction and validation
     */
    public function deleteJournal($journalId)
    {
        DB::beginTransaction();
        
        try {
            $journal = JournalMaster::with('journals')->findOrFail($journalId);
            
            // Check if journal is posted
            if ($journal->status === 'posted') {
                $this->dispatch('showAlert', [
                    'type' => 'error',
                    'message' => "Cannot delete posted journal '{$journal->journal_no}'. Only draft journals can be deleted."
                ]);
                
                DB::rollBack();
                return;
            }

            $journalNo = $journal->journal_no;
            
            // Delete journal details first
            $journal->journals()->delete();
            
            // Delete journal master
            $journal->delete();

            // Log the activity
            $logger = new ActivityLogger(storage_path('logs/activity'));
            $logger->log([
                'level' => 'info',
                'category' => 'journal_management',
                'message' => 'Journal entry deleted successfully',
                'meta' => [
                    'action' => 'journal_deleted',
                    'journal_id' => $journalId,
                    'journal_no' => $journalNo,
                    'user_id' => Auth::id(),
                    'user_name' => Auth::user()->name
                ]
            ]);

            DB::commit();

            $this->dispatch('showAlert', [
                'type' => 'success',
                'message' => "Journal entry '{$journalNo}' has been deleted successfully."
            ]);

            $this->dispatch('journalDeleted');

        } catch (\Exception $e) {
            DB::rollBack();
            
            $this->dispatch('showAlert', [
                'type' => 'error',
                'message' => 'Failed to delete journal entry: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Post Journal (change status from draft to posted)
     */
    public function postJournal($journalId)
    {
        DB::beginTransaction();
        
        try {
            $journal = JournalMaster::with('journals')->findOrFail($journalId);
            
            // Check if journal is already posted
            if ($journal->status === 'posted') {
                $this->dispatch('showAlert', [
                    'type' => 'warning',
                    'message' => "Journal '{$journal->journal_no}' is already posted."
                ]);
                
                DB::rollBack();
                return;
            }

            // Check if journal is balanced
            if (!$journal->is_balanced) {
                $this->dispatch('showAlert', [
                    'type' => 'error',
                    'message' => "Cannot post unbalanced journal '{$journal->journal_no}'. Debit and Credit amounts must be equal."
                ]);
                
                DB::rollBack();
                return;
            }

            // Update status to posted
            $journal->update([
                'status' => 'posted',
                'posted_at' => now()
            ]);

            // Log the activity
            $logger = new ActivityLogger(storage_path('logs/activity'));
            $logger->log([
                'level' => 'info',
                'category' => 'journal_management',
                'message' => 'Journal entry posted successfully',
                'meta' => [
                    'action' => 'journal_posted',
                    'journal_id' => $journalId,
                    'journal_no' => $journal->journal_no,
                    'user_id' => Auth::id(),
                    'user_name' => Auth::user()->name
                ]
            ]);

            DB::commit();

            $this->dispatch('showAlert', [
                'type' => 'success',
                'message' => "Journal entry '{$journal->journal_no}' has been posted successfully."
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            $this->dispatch('showAlert', [
                'type' => 'error',
                'message' => 'Failed to post journal entry: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Clear filters
     */
    public function clearFilters()
    {
        $this->search = '';
        $this->filterStatus = '';
        $this->filterPeriod = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.journal.journal-list', [
            'journals' => $this->journals,
            'periods' => $this->periods,
            'statuses' => [
                'draft' => 'Draft',
                'posted' => 'Posted',
                'cancelled' => 'Cancelled'
            ]
        ]);
    }
}
