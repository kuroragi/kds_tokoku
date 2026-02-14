<?php

namespace App\Livewire\FiscalCorrection;

use App\Models\FiscalCorrection;
use App\Models\Period;
use Livewire\Component;

class FiscalCorrectionIndex extends Component
{
    // Filter
    public $selectedYear;

    // Form
    public $showModal = false;
    public $isEditing = false;
    public $editId = null;
    public $description = '';
    public $correction_type = 'positive';
    public $category = 'beda_tetap';
    public $amount = 0;
    public $notes = '';

    protected $listeners = [
        'deleteFiscalCorrection' => 'delete',
    ];

    protected function rules()
    {
        return [
            'description' => 'required|string|max:255',
            'correction_type' => 'required|in:positive,negative',
            'category' => 'required|in:beda_tetap,beda_waktu',
            'amount' => 'required|numeric|min:1',
            'notes' => 'nullable|string',
        ];
    }

    public function mount()
    {
        $this->selectedYear = (int) date('Y');
    }

    public function updatedSelectedYear()
    {
        // refresh data
    }

    // Computed properties
    public function getCorrectionsProperty()
    {
        return FiscalCorrection::forYear($this->selectedYear)
            ->orderBy('correction_type')
            ->orderBy('category')
            ->get();
    }

    public function getSummaryProperty()
    {
        $corrections = $this->corrections;
        return [
            'total_positive' => $corrections->where('correction_type', 'positive')->sum('amount'),
            'total_negative' => $corrections->where('correction_type', 'negative')->sum('amount'),
            'count' => $corrections->count(),
        ];
    }

    public function getAvailableYearsProperty()
    {
        return Period::select('year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');
    }

    public function openModal()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit($id)
    {
        $correction = FiscalCorrection::findOrFail($id);
        $this->isEditing = true;
        $this->editId = $correction->id;
        $this->description = $correction->description;
        $this->correction_type = $correction->correction_type;
        $this->category = $correction->category;
        $this->amount = $correction->amount;
        $this->notes = $correction->notes;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        $data = [
            'year' => $this->selectedYear,
            'description' => $this->description,
            'correction_type' => $this->correction_type,
            'category' => $this->category,
            'amount' => $this->amount,
            'notes' => $this->notes,
        ];

        if ($this->isEditing) {
            FiscalCorrection::findOrFail($this->editId)->update($data);
            $message = 'Koreksi fiskal berhasil diperbarui.';
        } else {
            FiscalCorrection::create($data);
            $message = 'Koreksi fiskal berhasil ditambahkan.';
        }

        $this->dispatch('showAlert', ['type' => 'success', 'message' => $message]);
        $this->closeModal();
    }

    public function delete($id)
    {
        $correction = FiscalCorrection::findOrFail($id);
        $correction->delete();
        $this->dispatch('showAlert', ['type' => 'success', 'message' => 'Koreksi fiskal berhasil dihapus.']);
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->isEditing = false;
        $this->editId = null;
        $this->description = '';
        $this->correction_type = 'positive';
        $this->category = 'beda_tetap';
        $this->amount = 0;
        $this->notes = '';
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.fiscal-correction.fiscal-correction-index', [
            'corrections' => $this->corrections,
            'summary' => $this->summary,
            'availableYears' => $this->availableYears,
        ]);
    }
}
