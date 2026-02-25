<?php

namespace App\Livewire\AdjustmentJournal;

use App\Models\JournalMaster;
use App\Models\Period;
use App\Services\BusinessUnitService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Kuroragi\GeneralHelper\ActivityLog\ActivityLogger;
use Livewire\Component;
use Livewire\WithPagination;

class AdjustmentJournalList extends Component
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
        'refreshAdjustmentList' => '$refresh',
        'adjustmentDeleted' => '$refresh',
        'deleteAdjustment' => 'deleteAdjustment',
        'postAdjustment' => 'postAdjustment',
    ];

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

    public function getJournalsProperty()
    {
        $query = JournalMaster::with(['period', 'journals.coa'])
            ->where('type', 'adjustment');

        // Apply business unit scoping
        BusinessUnitService::applyBusinessUnitFilter($query);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('journal_no', 'like', "%{$this->search}%")
                  ->orWhere('reference', 'like', "%{$this->search}%")
                  ->orWhere('description', 'like', "%{$this->search}%");
            });
        }

        if ($this->filterStatus !== '') {
            $query->where('status', $this->filterStatus);
        }

        if ($this->filterPeriod) {
            $query->where('id_period', $this->filterPeriod);
        }

        if ($this->dateFrom) {
            $query->whereDate('journal_date', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate('journal_date', '<=', $this->dateTo);
        }

        $query->orderBy($this->sortField, $this->sortDirection);

        return $query->paginate($this->perPage);
    }

    public function getPeriodsProperty()
    {
        return Period::orderBy('year', 'desc')->orderBy('month', 'desc')->get();
    }

    public function deleteAdjustment($journalId)
    {
        DB::beginTransaction();

        try {
            $journal = JournalMaster::with('journals')
                ->where('type', 'adjustment')
                ->findOrFail($journalId);

            if ($journal->status === 'posted') {
                $this->dispatch('showAlert', [
                    'type' => 'error',
                    'message' => "Tidak dapat menghapus jurnal penyesuaian '{$journal->journal_no}' yang sudah diposting."
                ]);
                DB::rollBack();
                return;
            }

            $journalNo = $journal->journal_no;

            $journal->journals()->delete();
            $journal->delete();

            $logger = new ActivityLogger(storage_path('logs/activity'));
            $logger->log([
                'level' => 'info',
                'category' => 'adjustment_journal',
                'message' => 'Adjustment journal deleted',
                'meta' => [
                    'action' => 'adjustment_deleted',
                    'journal_id' => $journalId,
                    'journal_no' => $journalNo,
                    'user_id' => Auth::id(),
                    'user_name' => Auth::user()->name,
                ],
            ]);

            DB::commit();

            $this->dispatch('showAlert', [
                'type' => 'success',
                'message' => "Jurnal penyesuaian '{$journalNo}' berhasil dihapus.",
            ]);

            $this->dispatch('adjustmentDeleted');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('showAlert', [
                'type' => 'error',
                'message' => 'Gagal menghapus jurnal penyesuaian: ' . $e->getMessage(),
            ]);
        }
    }

    public function postAdjustment($journalId)
    {
        DB::beginTransaction();

        try {
            $journal = JournalMaster::with('journals')
                ->where('type', 'adjustment')
                ->findOrFail($journalId);

            if ($journal->status === 'posted') {
                $this->dispatch('showAlert', [
                    'type' => 'warning',
                    'message' => "Jurnal penyesuaian '{$journal->journal_no}' sudah diposting.",
                ]);
                DB::rollBack();
                return;
            }

            if (!$journal->is_balanced) {
                $this->dispatch('showAlert', [
                    'type' => 'error',
                    'message' => "Tidak dapat memposting jurnal '{$journal->journal_no}' yang tidak seimbang.",
                ]);
                DB::rollBack();
                return;
            }

            $journal->update([
                'status' => 'posted',
                'posted_at' => now(),
            ]);

            $logger = new ActivityLogger(storage_path('logs/activity'));
            $logger->log([
                'level' => 'info',
                'category' => 'adjustment_journal',
                'message' => 'Adjustment journal posted',
                'meta' => [
                    'action' => 'adjustment_posted',
                    'journal_id' => $journalId,
                    'journal_no' => $journal->journal_no,
                    'user_id' => Auth::id(),
                    'user_name' => Auth::user()->name,
                ],
            ]);

            DB::commit();

            $this->dispatch('showAlert', [
                'type' => 'success',
                'message' => "Jurnal penyesuaian '{$journal->journal_no}' berhasil diposting.",
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('showAlert', [
                'type' => 'error',
                'message' => 'Gagal memposting jurnal penyesuaian: ' . $e->getMessage(),
            ]);
        }
    }

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
        return view('livewire.adjustment-journal.adjustment-journal-list', [
            'journals' => $this->journals,
            'periods' => $this->periods,
            'statuses' => [
                'draft' => 'Draft',
                'posted' => 'Posted',
                'cancelled' => 'Cancelled',
            ],
        ]);
    }
}
