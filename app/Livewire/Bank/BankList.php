<?php

namespace App\Livewire\Bank;

use App\Models\Bank;
use App\Models\BankFeeMatrix;
use Livewire\Component;

class BankList extends Component
{
    public $search = '';
    public $filterStatus = '';
    public $sortField = 'code';
    public $sortDirection = 'asc';

    protected $listeners = ['refreshBankList' => '$refresh'];

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function getBanksProperty()
    {
        $query = Bank::withCount('bankAccounts');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('code', 'like', "%{$this->search}%")
                  ->orWhere('name', 'like', "%{$this->search}%")
                  ->orWhere('swift_code', 'like', "%{$this->search}%");
            });
        }

        if ($this->filterStatus !== '') {
            $query->where('is_active', (bool) $this->filterStatus);
        }

        return $query->orderBy($this->sortField, $this->sortDirection)->get();
    }

    public function getFeeMatrixProperty()
    {
        return BankFeeMatrix::with(['sourceBank', 'destinationBank'])
            ->orderBy('source_bank_id')
            ->orderBy('destination_bank_id')
            ->get();
    }

    public function toggleStatus($id)
    {
        $bank = Bank::findOrFail($id);
        $bank->is_active = !$bank->is_active;
        $bank->save();

        $status = $bank->is_active ? 'diaktifkan' : 'dinonaktifkan';
        $this->dispatch('alert', type: 'success', message: "Bank '{$bank->name}' berhasil {$status}.");
    }

    public function deleteBank($id)
    {
        $bank = Bank::findOrFail($id);

        if ($bank->bankAccounts()->count() > 0) {
            $this->dispatch('alert', type: 'error', message: "Bank '{$bank->name}' tidak dapat dihapus karena memiliki rekening terkait.");
            return;
        }

        $bank->delete();
        $this->dispatch('alert', type: 'success', message: "Bank '{$bank->name}' berhasil dihapus.");
    }

    public function deleteFeeMatrix($id)
    {
        $matrix = BankFeeMatrix::findOrFail($id);
        $matrix->delete();
        $this->dispatch('alert', type: 'success', message: 'Fee matrix berhasil dihapus.');
    }

    public function render()
    {
        return view('livewire.bank.bank-list', [
            'banks' => $this->banks,
            'feeMatrix' => $this->feeMatrix,
        ]);
    }
}
