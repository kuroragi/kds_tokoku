<?php

namespace App\Livewire\AdjustmentJournal;

use App\Models\JournalMaster;
use App\Models\Journal;
use App\Models\COA;
use App\Models\Period;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Kuroragi\GeneralHelper\ActivityLog\ActivityLogger;
use Livewire\Component;

class AdjustmentJournalForm extends Component
{
    // Form Properties
    public $journalId;
    public $journal_no = '';
    public $journal_date = '';
    public $reference = '';
    public $description = '';
    public $id_period;

    // Journal Details
    public $journalDetails = [];

    // UI State
    public $showModal = false;
    public $isEditing = false;

    // Calculated totals
    public $totalDebit = 0;
    public $totalCredit = 0;

    // Listeners
    protected $listeners = [
        'openAdjustmentModal' => 'openModal',
        'editAdjustment' => 'edit',
    ];

    protected function rules()
    {
        return [
            'journal_date' => 'required|date',
            'reference' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'id_period' => 'required|exists:periods,id',
            'journalDetails' => 'required|array|min:2',
            'journalDetails.*.id_coa' => 'required|exists:c_o_a_s,id',
            'journalDetails.*.description' => 'nullable|string',
            'journalDetails.*.debit' => 'required|numeric|min:0',
            'journalDetails.*.credit' => 'required|numeric|min:0',
        ];
    }

    public function mount()
    {
        $this->journal_date = now()->format('Y-m-d');
        $this->addJournalRow();
        $this->addJournalRow();
    }

    public function updated($propertyName)
    {
        if (!$this->showModal) return;

        if (str_contains($propertyName, 'journalDetails')) {
            $this->calculateTotals();
        }

        if ($propertyName === 'journal_date') {
            $this->findMatchingPeriod();
            $this->validateOnly($propertyName);
        }

        if (in_array($propertyName, ['reference', 'description', 'id_period'])) {
            $this->validateOnly($propertyName);
        }
    }

    public function findMatchingPeriod()
    {
        if (!$this->journal_date) return;

        $periods = $this->periods;
        $selectedDate = $this->journal_date;

        foreach ($periods as $period) {
            if ($selectedDate >= $period->start_date && $selectedDate <= $period->end_date) {
                $this->id_period = $period->id;
                $this->dispatch('showAlert', [
                    'type' => 'info',
                    'message' => "Periode '{$period->period_name}' otomatis dipilih berdasarkan tanggal.",
                ]);
                return;
            }
        }

        $this->id_period = null;
        $this->dispatch('showAlert', [
            'type' => 'warning',
            'message' => 'Tidak ada periode yang sesuai dengan tanggal ini.',
        ]);
    }

    public function openModal()
    {
        if (!$this->isEditing) {
            $this->resetForm();
            $this->generateJournalNumber();
            $this->findMatchingPeriod();
        }
        $this->showModal = true;
    }

    public function edit($journalId)
    {
        $this->isEditing = true;

        try {
            $journal = JournalMaster::with(['journals.coa', 'period'])
                ->where('type', 'adjustment')
                ->findOrFail($journalId);

            $this->journalId = $journal->id;
            $this->journal_no = $journal->journal_no;
            $this->journal_date = $journal->journal_date->format('Y-m-d');
            $this->reference = $journal->reference;
            $this->description = $journal->description;
            $this->id_period = $journal->id_period;

            $this->journalDetails = [];
            foreach ($journal->journals as $detail) {
                $this->journalDetails[] = [
                    'id' => $detail->id,
                    'id_coa' => $detail->id_coa,
                    'description' => $detail->description,
                    'debit' => $detail->debit,
                    'credit' => $detail->credit,
                ];
            }

            $this->calculateTotals();
            $this->showModal = true;

        } catch (\Exception $e) {
            $this->dispatch('showAlert', [
                'type' => 'error',
                'message' => 'Jurnal penyesuaian tidak ditemukan.',
            ]);
        }
    }

    public function addJournalRow()
    {
        $this->journalDetails[] = [
            'id' => null,
            'id_coa' => '',
            'description' => '',
            'debit' => 0,
            'credit' => 0,
        ];
    }

    public function removeJournalRow($index)
    {
        if (count($this->journalDetails) > 2) {
            unset($this->journalDetails[$index]);
            $this->journalDetails = array_values($this->journalDetails);
            $this->calculateTotals();
        }
    }

    public function calculateTotals()
    {
        $this->totalDebit = collect($this->journalDetails)->sum('debit');
        $this->totalCredit = collect($this->journalDetails)->sum('credit');
    }

    public function save()
    {
        $this->calculateTotals();
        $this->validate();

        if ($this->totalDebit != $this->totalCredit) {
            $this->dispatch('showAlert', [
                'type' => 'error',
                'message' => 'Jurnal tidak seimbang. Total Debit harus sama dengan Total Kredit.',
            ]);
            return;
        }

        DB::beginTransaction();

        try {
            $data = [
                'type' => 'adjustment',
                'journal_no' => $this->journal_no,
                'journal_date' => $this->journal_date,
                'reference' => $this->reference,
                'description' => $this->description,
                'id_period' => $this->id_period,
                'total_debit' => $this->totalDebit,
                'total_credit' => $this->totalCredit,
                'status' => 'draft',
            ];

            if ($this->isEditing) {
                $journal = JournalMaster::where('type', 'adjustment')->findOrFail($this->journalId);
                $journal->update($data);
                $journal->journals()->delete();
                $message = "Jurnal penyesuaian '{$journal->journal_no}' berhasil diperbarui.";
                $action = 'adjustment_updated';
            } else {
                $journal = JournalMaster::create($data);
                $message = "Jurnal penyesuaian '{$journal->journal_no}' berhasil dibuat.";
                $action = 'adjustment_created';
            }

            foreach ($this->journalDetails as $index => $detail) {
                if ($detail['id_coa'] && ($detail['debit'] > 0 || $detail['credit'] > 0)) {
                    Journal::create([
                        'id_journal_master' => $journal->id,
                        'id_coa' => $detail['id_coa'],
                        'description' => $detail['description'],
                        'debit' => $detail['debit'],
                        'credit' => $detail['credit'],
                        'sequence' => $index + 1,
                    ]);
                }
            }

            $logger = new ActivityLogger(storage_path('logs/activity'));
            $logger->log([
                'level' => 'info',
                'category' => 'adjustment_journal',
                'message' => $this->isEditing ? 'Adjustment journal updated' : 'Adjustment journal created',
                'meta' => [
                    'action' => $action,
                    'journal_id' => $journal->id,
                    'journal_no' => $journal->journal_no,
                    'user_id' => Auth::id(),
                    'user_name' => Auth::user()->name,
                ],
            ]);

            DB::commit();

            $this->dispatch('showAlert', [
                'type' => 'success',
                'message' => $message,
            ]);

            $this->dispatch('refreshAdjustmentList');
            $this->closeModal();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('showAlert', [
                'type' => 'error',
                'message' => 'Gagal menyimpan jurnal penyesuaian: ' . $e->getMessage(),
            ]);
        }
    }

    private function generateJournalNumber()
    {
        $date = now();
        $prefix = 'AJE';
        $year = $date->format('Y');
        $month = $date->format('m');

        $lastJournal = JournalMaster::where('journal_no', 'like', "{$prefix}/{$year}/{$month}/%")
            ->orderBy('journal_no', 'desc')
            ->first();

        if ($lastJournal) {
            $lastNumber = (int) substr($lastJournal->journal_no, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        $this->journal_no = "{$prefix}/{$year}/{$month}/" . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->journalId = null;
        $this->journal_no = '';
        $this->journal_date = now()->format('Y-m-d');
        $this->reference = '';
        $this->description = '';
        $this->id_period = null;
        $this->journalDetails = [];
        $this->totalDebit = 0;
        $this->totalCredit = 0;
        $this->isEditing = false;
        $this->resetErrorBag();
        $this->resetValidation();

        $this->addJournalRow();
        $this->addJournalRow();
    }

    public function getCoasProperty()
    {
        return COA::active()
            ->leafAccounts()
            ->orderBy('code')
            ->get();
    }

    public function getPeriodsProperty()
    {
        return Period::orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();
    }

    public function render()
    {
        return view('livewire.adjustment-journal.adjustment-journal-form', [
            'coas' => $this->coas,
            'periods' => $this->periods,
        ]);
    }
}
