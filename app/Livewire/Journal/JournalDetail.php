<?php

namespace App\Livewire\Journal;

use App\Models\JournalMaster;
use Livewire\Component;

class JournalDetail extends Component
{
    public $journalId;
    public $journal;
    public $showModal = false;

    protected $listeners = [
        'viewJournalDetail' => 'viewDetail'
    ];

    public function viewDetail($journalId)
    {
        try {
            $this->journalId = $journalId;
            $this->journal = JournalMaster::with(['journals.coa', 'period'])
                ->findOrFail($journalId);
            
            $this->showModal = true;
        } catch (\Exception $e) {
            $this->dispatch('showAlert', [
                'type' => 'error',
                'message' => 'Journal not found.'
            ]);
        }
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->journal = null;
        $this->journalId = null;
    }

    public function render()
    {
        return view('livewire.journal.journal-detail');
    }
}