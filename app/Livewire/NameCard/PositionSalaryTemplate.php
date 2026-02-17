<?php

namespace App\Livewire\NameCard;

use App\Models\Position;
use App\Models\PositionSalaryComponent;
use App\Models\SalaryComponent;
use Livewire\Component;

class PositionSalaryTemplate extends Component
{
    public bool $showModal = false;
    public ?int $positionId = null;
    public string $positionName = '';
    public array $assignments = [];
    public $availableComponents = [];

    // Add new row
    public $newComponentId = '';
    public $newAmount = '';

    protected $listeners = ['openPositionSalaryTemplate'];

    public function openPositionSalaryTemplate($id)
    {
        $position = Position::findOrFail($id);
        $this->positionId = $position->id;
        $this->positionName = $position->name;

        $this->loadAssignments();
        $this->loadAvailableComponents($position->business_unit_id);
        $this->showModal = true;
    }

    public function loadAssignments()
    {
        $this->assignments = PositionSalaryComponent::where('position_id', $this->positionId)
            ->with('salaryComponent')
            ->get()
            ->map(fn($psc) => [
                'id' => $psc->id,
                'salary_component_id' => $psc->salary_component_id,
                'component_name' => $psc->salaryComponent->name ?? '-',
                'component_code' => $psc->salaryComponent->code ?? '-',
                'amount' => $psc->amount,
            ])
            ->toArray();
    }

    public function loadAvailableComponents($businessUnitId)
    {
        $existingIds = collect($this->assignments)->pluck('salary_component_id')->toArray();

        $this->availableComponents = SalaryComponent::byBusinessUnit($businessUnitId)
            ->active()
            ->template()
            ->whereNotIn('id', $existingIds)
            ->orderBy('sort_order')
            ->get()
            ->toArray();
    }

    public function addComponent()
    {
        if (!$this->newComponentId || $this->newAmount === '') {
            return;
        }

        PositionSalaryComponent::create([
            'position_id' => $this->positionId,
            'salary_component_id' => $this->newComponentId,
            'amount' => (int) $this->newAmount,
        ]);

        $this->newComponentId = '';
        $this->newAmount = '';
        $this->loadAssignments();

        $position = Position::find($this->positionId);
        if ($position) {
            $this->loadAvailableComponents($position->business_unit_id);
        }

        $this->dispatch('alert', type: 'success', message: 'Komponen gaji berhasil ditambahkan ke template.');
    }

    public function updateAmount($assignmentId, $amount)
    {
        PositionSalaryComponent::where('id', $assignmentId)->update([
            'amount' => (int) $amount,
        ]);
        $this->dispatch('alert', type: 'success', message: 'Nominal berhasil diperbarui.');
    }

    public function removeComponent($assignmentId)
    {
        PositionSalaryComponent::where('id', $assignmentId)->delete();
        $this->loadAssignments();

        $position = Position::find($this->positionId);
        if ($position) {
            $this->loadAvailableComponents($position->business_unit_id);
        }

        $this->dispatch('alert', type: 'success', message: 'Komponen gaji berhasil dihapus dari template.');
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->positionId = null;
        $this->positionName = '';
        $this->assignments = [];
        $this->availableComponents = [];
        $this->newComponentId = '';
        $this->newAmount = '';
    }

    public function render()
    {
        return view('livewire.name-card.position-salary-template');
    }
}
