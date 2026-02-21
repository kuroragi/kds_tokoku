<?php

namespace App\Livewire\Project;

use App\Models\Customer;
use App\Models\Project;
use App\Services\BusinessUnitService;
use App\Services\ProjectService;
use Livewire\Component;
use Livewire\WithPagination;

class ProjectList extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    // Filters
    public string $search = '';
    public string $filterUnit = '';
    public string $filterStatus = '';

    // Create/Edit modal
    public bool $showFormModal = false;
    public bool $isEditing = false;
    public ?int $editingId = null;
    public string $name = '';
    public string $description = '';
    public ?int $customer_id = null;
    public string $start_date = '';
    public ?string $end_date = null;
    public float $budget = 0;
    public string $status = 'planning';
    public string $notes = '';

    // Detail modal
    public bool $showDetailModal = false;
    public ?Project $detailProject = null;
    public array $summary = [];
    public array $costItems = [];
    public array $revenueItems = [];
    public string $detailTab = 'overview'; // overview, costs, revenues

    // Add Cost/Revenue modal
    public bool $showCostForm = false;
    public bool $showRevenueForm = false;
    public string $item_date = '';
    public string $item_category = 'material';
    public string $item_description = '';
    public float $item_amount = 0;
    public string $item_notes = '';

    protected $queryString = ['search', 'filterStatus'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    // ─── Computed ───
    public function getProjectsProperty()
    {
        $query = Project::query()
            ->with(['businessUnit', 'customer'])
            ->when($this->search, fn($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('project_code', 'like', "%{$s}%")
                  ->orWhere('name', 'like', "%{$s}%");
            }))
            ->when($this->filterStatus, fn($q, $s) => $q->where('status', $s))
            ->orderByDesc('created_at');

        BusinessUnitService::applyBusinessUnitFilter($query, $this->filterUnit);

        return $query->paginate(15);
    }

    public function getIsSuperAdminProperty(): bool
    {
        return BusinessUnitService::isSuperAdmin();
    }

    public function getUnitsProperty()
    {
        return BusinessUnitService::getAvailableUnits();
    }

    public function getCustomersProperty()
    {
        $buId = BusinessUnitService::getUserBusinessUnitId();
        $q = Customer::query()->orderBy('name');
        if ($buId) $q->where('business_unit_id', $buId);
        return $q->get();
    }

    // ─── Create / Edit ───
    public function openCreate()
    {
        $this->resetFormFields();
        $this->isEditing = false;
        $this->start_date = now()->format('Y-m-d');
        $this->showFormModal = true;
    }

    public function openEdit(int $id)
    {
        $project = Project::findOrFail($id);
        $this->editingId = $project->id;
        $this->isEditing = true;
        $this->name = $project->name;
        $this->description = $project->description ?? '';
        $this->customer_id = $project->customer_id;
        $this->start_date = $project->start_date->format('Y-m-d');
        $this->end_date = $project->end_date?->format('Y-m-d');
        $this->budget = (float) $project->budget;
        $this->status = $project->status;
        $this->notes = $project->notes ?? '';
        $this->showFormModal = true;
    }

    public function closeForm()
    {
        $this->showFormModal = false;
        $this->resetFormFields();
    }

    public function saveProject()
    {
        $this->validate([
            'name' => 'required|min:3',
            'start_date' => 'required|date',
            'budget' => 'required|numeric|min:0',
            'status' => 'required|in:planning,active,on_hold,completed,cancelled',
        ]);

        $data = [
            'business_unit_id' => BusinessUnitService::resolveBusinessUnitId($this->filterUnit),
            'name' => $this->name,
            'description' => $this->description ?: null,
            'customer_id' => $this->customer_id ?: null,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date ?: null,
            'budget' => $this->budget,
            'status' => $this->status,
            'notes' => $this->notes ?: null,
        ];

        try {
            if ($this->isEditing) {
                $project = Project::findOrFail($this->editingId);
                ProjectService::updateProject($project, $data);
                $this->dispatch('alert', type: 'success', message: 'Proyek berhasil diperbarui.');
            } else {
                ProjectService::createProject($data);
                $this->dispatch('alert', type: 'success', message: 'Proyek berhasil dibuat.');
            }
            $this->closeForm();
        } catch (\Exception $e) {
            $this->dispatch('alert', type: 'danger', message: 'Error: ' . $e->getMessage());
        }
    }

    public function deleteProject(int $id)
    {
        try {
            $project = Project::findOrFail($id);
            ProjectService::deleteProject($project);
            $this->dispatch('alert', type: 'success', message: 'Proyek berhasil dihapus.');
        } catch (\Exception $e) {
            $this->dispatch('alert', type: 'danger', message: $e->getMessage());
        }
    }

    // ─── Detail ───
    public function openDetail(int $id)
    {
        $this->detailProject = Project::with(['customer', 'businessUnit'])->findOrFail($id);
        $this->summary = ProjectService::getSummary($this->detailProject);
        $this->loadDetailItems();
        $this->detailTab = 'overview';
        $this->showDetailModal = true;
    }

    public function closeDetail()
    {
        $this->showDetailModal = false;
        $this->detailProject = null;
    }

    private function loadDetailItems()
    {
        if (!$this->detailProject) return;

        $this->costItems = $this->detailProject->costs()
            ->orderByDesc('cost_date')
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'date' => $c->cost_date->format('d/m/Y'),
                'category' => Project::COST_CATEGORIES[$c->category] ?? $c->category,
                'category_key' => $c->category,
                'description' => $c->description,
                'amount' => (float) $c->amount,
                'notes' => $c->notes,
            ])->toArray();

        $this->revenueItems = $this->detailProject->revenues()
            ->orderByDesc('revenue_date')
            ->get()
            ->map(fn($r) => [
                'id' => $r->id,
                'date' => $r->revenue_date->format('d/m/Y'),
                'description' => $r->description,
                'amount' => (float) $r->amount,
                'notes' => $r->notes,
            ])->toArray();
    }

    // ─── Add Cost ───
    public function openCostForm()
    {
        $this->resetItemForm();
        $this->item_date = now()->format('Y-m-d');
        $this->showCostForm = true;
    }

    public function closeCostForm()
    {
        $this->showCostForm = false;
    }

    public function saveCost()
    {
        $this->validate([
            'item_date' => 'required|date',
            'item_category' => 'required|in:material,labor,overhead,other',
            'item_description' => 'required|min:3',
            'item_amount' => 'required|numeric|min:0.01',
        ]);

        try {
            ProjectService::addCost($this->detailProject->id, [
                'cost_date' => $this->item_date,
                'category' => $this->item_category,
                'description' => $this->item_description,
                'amount' => $this->item_amount,
                'notes' => $this->item_notes ?: null,
            ]);

            $this->detailProject->refresh();
            $this->summary = ProjectService::getSummary($this->detailProject);
            $this->loadDetailItems();
            $this->closeCostForm();
            $this->dispatch('alert', type: 'success', message: 'Biaya berhasil ditambahkan.');
        } catch (\Exception $e) {
            $this->dispatch('alert', type: 'danger', message: 'Error: ' . $e->getMessage());
        }
    }

    public function deleteCost(int $id)
    {
        try {
            $cost = \App\Models\ProjectCost::findOrFail($id);
            ProjectService::deleteCost($cost);
            $this->detailProject->refresh();
            $this->summary = ProjectService::getSummary($this->detailProject);
            $this->loadDetailItems();
            $this->dispatch('alert', type: 'success', message: 'Biaya berhasil dihapus.');
        } catch (\Exception $e) {
            $this->dispatch('alert', type: 'danger', message: 'Error: ' . $e->getMessage());
        }
    }

    // ─── Add Revenue ───
    public function openRevenueForm()
    {
        $this->resetItemForm();
        $this->item_date = now()->format('Y-m-d');
        $this->showRevenueForm = true;
    }

    public function closeRevenueForm()
    {
        $this->showRevenueForm = false;
    }

    public function saveRevenue()
    {
        $this->validate([
            'item_date' => 'required|date',
            'item_description' => 'required|min:3',
            'item_amount' => 'required|numeric|min:0.01',
        ]);

        try {
            ProjectService::addRevenue($this->detailProject->id, [
                'revenue_date' => $this->item_date,
                'description' => $this->item_description,
                'amount' => $this->item_amount,
                'notes' => $this->item_notes ?: null,
            ]);

            $this->detailProject->refresh();
            $this->summary = ProjectService::getSummary($this->detailProject);
            $this->loadDetailItems();
            $this->closeRevenueForm();
            $this->dispatch('alert', type: 'success', message: 'Pendapatan berhasil ditambahkan.');
        } catch (\Exception $e) {
            $this->dispatch('alert', type: 'danger', message: 'Error: ' . $e->getMessage());
        }
    }

    public function deleteRevenue(int $id)
    {
        try {
            $revenue = \App\Models\ProjectRevenue::findOrFail($id);
            ProjectService::deleteRevenue($revenue);
            $this->detailProject->refresh();
            $this->summary = ProjectService::getSummary($this->detailProject);
            $this->loadDetailItems();
            $this->dispatch('alert', type: 'success', message: 'Pendapatan berhasil dihapus.');
        } catch (\Exception $e) {
            $this->dispatch('alert', type: 'danger', message: 'Error: ' . $e->getMessage());
        }
    }

    // ─── Change Status ───
    public function changeStatus(int $id, string $status)
    {
        try {
            $project = Project::findOrFail($id);
            ProjectService::changeStatus($project, $status);
            if ($this->detailProject && $this->detailProject->id === $id) {
                $this->detailProject->refresh();
                $this->summary = ProjectService::getSummary($this->detailProject);
            }
            $this->dispatch('alert', type: 'success', message: 'Status proyek diperbarui.');
        } catch (\Exception $e) {
            $this->dispatch('alert', type: 'danger', message: $e->getMessage());
        }
    }

    // ─── Helpers ───
    private function resetFormFields()
    {
        $this->editingId = null;
        $this->name = '';
        $this->description = '';
        $this->customer_id = null;
        $this->start_date = '';
        $this->end_date = null;
        $this->budget = 0;
        $this->status = 'planning';
        $this->notes = '';
    }

    private function resetItemForm()
    {
        $this->item_date = '';
        $this->item_category = 'material';
        $this->item_description = '';
        $this->item_amount = 0;
        $this->item_notes = '';
    }

    public function render()
    {
        return view('livewire.project.project-list');
    }
}
