<?php

namespace App\Livewire\Report;

use App\Models\TaxInvoice;
use App\Services\BusinessUnitService;
use App\Services\TaxReportService;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class TaxReportIndex extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    // Tab
    public string $activeTab = 'faktur'; // faktur, spt_masa, spt_tahunan

    // Filters
    public string $filterUnit = '';
    public string $filterType = '';
    public string $filterStatus = '';
    public string $filterPeriod = '';
    public string $search = '';

    // SPT
    public string $sptPeriod = '';
    public int $sptYear = 0;
    public array $sptMasa = [];
    public array $sptTahunan = [];

    // Create/Edit Faktur
    public bool $showFakturForm = false;
    public bool $isEditingFaktur = false;
    public ?int $editingFakturId = null;
    public string $faktur_type = 'keluaran';
    public string $faktur_number = '';
    public string $faktur_date = '';
    public string $faktur_partner_name = '';
    public string $faktur_partner_npwp = '';
    public float $faktur_dpp = 0;
    public float $faktur_ppn = 0;
    public float $faktur_ppnbm = 0;
    public string $faktur_notes = '';

    // Generate
    public bool $showGenerateModal = false;
    public string $generate_period = '';

    public function mount()
    {
        $this->filterPeriod = now()->format('Y-m');
        $this->sptPeriod = now()->format('Y-m');
        $this->sptYear = (int) now()->format('Y');
    }

    public function updatingSearch() { $this->resetPage(); }
    public function updatingFilterType() { $this->resetPage(); }
    public function updatingFilterStatus() { $this->resetPage(); }
    public function updatingFilterPeriod() { $this->resetPage(); }

    // ─── Computed ───
    public function getFakturProperty()
    {
        $query = TaxInvoice::query()
            ->when($this->search, fn($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('partner_name', 'like', "%{$s}%")
                  ->orWhere('faktur_number', 'like', "%{$s}%");
            }))
            ->when($this->filterType, fn($q, $t) => $q->where('invoice_type', $t))
            ->when($this->filterStatus, fn($q, $s) => $q->where('status', $s))
            ->when($this->filterPeriod, fn($q, $p) => $q->where('tax_period', $p))
            ->orderByDesc('invoice_date');

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

    // ─── SPT Masa ───
    public function loadSptMasa()
    {
        $buId = $this->filterUnit ? (int) $this->filterUnit : BusinessUnitService::getUserBusinessUnitId();
        $this->sptMasa = TaxReportService::getSptMasaPPN($buId, $this->sptPeriod);
    }

    public function updatedSptPeriod()
    {
        $this->loadSptMasa();
    }

    // ─── SPT Tahunan ───
    public function loadSptTahunan()
    {
        $buId = $this->filterUnit ? (int) $this->filterUnit : BusinessUnitService::getUserBusinessUnitId();
        $this->sptTahunan = TaxReportService::getSptTahunan($buId, $this->sptYear);
    }

    public function updatedSptYear()
    {
        $this->loadSptTahunan();
    }

    // ─── Tab switching ───
    public function switchTab(string $tab)
    {
        $this->activeTab = $tab;
        if ($tab === 'spt_masa') $this->loadSptMasa();
        elseif ($tab === 'spt_tahunan') $this->loadSptTahunan();
    }

    // ─── Faktur CRUD ───
    public function openCreateFaktur()
    {
        $this->resetFakturForm();
        $this->faktur_date = now()->format('Y-m-d');
        $this->isEditingFaktur = false;
        $this->showFakturForm = true;
    }

    public function openEditFaktur(int $id)
    {
        $fi = TaxInvoice::findOrFail($id);
        $this->editingFakturId = $fi->id;
        $this->isEditingFaktur = true;
        $this->faktur_type = $fi->invoice_type;
        $this->faktur_number = $fi->faktur_number ?? '';
        $this->faktur_date = $fi->invoice_date->format('Y-m-d');
        $this->faktur_partner_name = $fi->partner_name;
        $this->faktur_partner_npwp = $fi->partner_npwp ?? '';
        $this->faktur_dpp = (float) $fi->dpp;
        $this->faktur_ppn = (float) $fi->ppn;
        $this->faktur_ppnbm = (float) $fi->ppnbm;
        $this->faktur_notes = $fi->notes ?? '';
        $this->showFakturForm = true;
    }

    public function closeFakturForm()
    {
        $this->showFakturForm = false;
    }

    public function saveFaktur()
    {
        $this->validate([
            'faktur_type' => 'required|in:keluaran,masukan',
            'faktur_date' => 'required|date',
            'faktur_partner_name' => 'required|min:2',
            'faktur_dpp' => 'required|numeric|min:0',
        ]);

        $data = [
            'business_unit_id' => BusinessUnitService::resolveBusinessUnitId($this->filterUnit),
            'invoice_type' => $this->faktur_type,
            'faktur_number' => $this->faktur_number ?: null,
            'invoice_date' => $this->faktur_date,
            'partner_name' => $this->faktur_partner_name,
            'partner_npwp' => $this->faktur_partner_npwp ?: null,
            'dpp' => $this->faktur_dpp,
            'ppn' => $this->faktur_ppn ?: null,
            'ppnbm' => $this->faktur_ppnbm,
            'notes' => $this->faktur_notes ?: null,
        ];

        try {
            if ($this->isEditingFaktur) {
                $fi = TaxInvoice::findOrFail($this->editingFakturId);
                TaxReportService::updateTaxInvoice($fi, $data);
                $this->dispatch('alert', type: 'success', message: 'Faktur berhasil diperbarui.');
            } else {
                TaxReportService::createTaxInvoice($data);
                $this->dispatch('alert', type: 'success', message: 'Faktur berhasil dibuat.');
            }
            $this->closeFakturForm();
        } catch (\Exception $e) {
            $this->dispatch('alert', type: 'danger', message: 'Error: ' . $e->getMessage());
        }
    }

    public function changeStatusFaktur(int $id, string $status)
    {
        try {
            $fi = TaxInvoice::findOrFail($id);
            TaxReportService::changeStatus($fi, $status);
            $this->dispatch('alert', type: 'success', message: 'Status faktur diperbarui.');
        } catch (\Exception $e) {
            $this->dispatch('alert', type: 'danger', message: $e->getMessage());
        }
    }

    public function deleteFaktur(int $id)
    {
        try {
            $fi = TaxInvoice::findOrFail($id);
            TaxReportService::deleteTaxInvoice($fi);
            $this->dispatch('alert', type: 'success', message: 'Faktur berhasil dihapus.');
        } catch (\Exception $e) {
            $this->dispatch('alert', type: 'danger', message: $e->getMessage());
        }
    }

    // ─── Generate from transactions ───
    public function openGenerate()
    {
        $this->generate_period = now()->format('Y-m');
        $this->showGenerateModal = true;
    }

    public function closeGenerate()
    {
        $this->showGenerateModal = false;
    }

    public function generateFaktur()
    {
        $buId = BusinessUnitService::resolveBusinessUnitId($this->filterUnit);

        try {
            $salesCount = TaxReportService::generateFromSales($buId, $this->generate_period);
            $purchaseCount = TaxReportService::generateFromPurchases($buId, $this->generate_period);

            $this->closeGenerate();
            $this->dispatch('alert', type: 'success', message: "Berhasil generate {$salesCount} faktur keluaran dan {$purchaseCount} faktur masukan.");
        } catch (\Exception $e) {
            $this->dispatch('alert', type: 'danger', message: 'Error: ' . $e->getMessage());
        }
    }

    // ─── Helpers ───
    private function resetFakturForm()
    {
        $this->editingFakturId = null;
        $this->faktur_type = 'keluaran';
        $this->faktur_number = '';
        $this->faktur_date = '';
        $this->faktur_partner_name = '';
        $this->faktur_partner_npwp = '';
        $this->faktur_dpp = 0;
        $this->faktur_ppn = 0;
        $this->faktur_ppnbm = 0;
        $this->faktur_notes = '';
    }

    public function render()
    {
        return view('livewire.report.tax-report-index');
    }
}
