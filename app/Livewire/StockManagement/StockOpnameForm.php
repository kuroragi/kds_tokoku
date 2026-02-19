<?php

namespace App\Livewire\StockManagement;

use App\Models\Stock;
use App\Services\BusinessUnitService;
use App\Services\StockOpnameService;
use Livewire\Component;

class StockOpnameForm extends Component
{
    public bool $showModal = false;

    // Header fields
    public $business_unit_id = '';
    public $opname_date = '';
    public $pic_name = '';
    public $notes = '';

    // Details
    public array $details = [];

    protected $listeners = ['openStockOpnameModal'];

    public function openStockOpnameModal()
    {
        $this->resetForm();
        $this->business_unit_id = BusinessUnitService::getDefaultBusinessUnitId();
        $this->opname_date = date('Y-m-d');
        $this->showModal = true;
    }

    public function updatedBusinessUnitId()
    {
        $this->loadStocks();
    }

    public function loadStocks()
    {
        if (!$this->business_unit_id) {
            $this->details = [];
            return;
        }

        $stocks = Stock::active()
            ->byBusinessUnit($this->business_unit_id)
            ->orderBy('name')
            ->get();

        $this->details = [];
        foreach ($stocks as $stock) {
            $this->details[] = [
                'stock_id' => $stock->id,
                'stock_name' => $stock->name,
                'stock_code' => $stock->code,
                'unit' => $stock->unitOfMeasure?->abbreviation ?? '-',
                'system_qty' => (float) $stock->current_stock,
                'actual_qty' => (float) $stock->current_stock,
                'difference' => 0,
                'notes' => '',
            ];
        }
    }

    public function updatedDetails($value, $key)
    {
        $parts = explode('.', $key);
        if (count($parts) === 2 && $parts[1] === 'actual_qty') {
            $index = $parts[0];
            $systemQty = (float) ($this->details[$index]['system_qty'] ?? 0);
            $actualQty = (float) ($value ?: 0);
            $this->details[$index]['difference'] = $actualQty - $systemQty;
        }
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->business_unit_id = '';
        $this->opname_date = '';
        $this->pic_name = '';
        $this->notes = '';
        $this->details = [];
        $this->resetValidation();
    }

    protected function rules(): array
    {
        return [
            'business_unit_id' => 'required|exists:business_units,id',
            'opname_date' => 'required|date',
            'pic_name' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
            'details' => 'required|array|min:1',
            'details.*.stock_id' => 'required|exists:stocks,id',
            'details.*.actual_qty' => 'required|numeric|min:0',
        ];
    }

    protected $messages = [
        'business_unit_id.required' => 'Unit usaha wajib dipilih.',
        'opname_date.required' => 'Tanggal opname wajib diisi.',
        'details.required' => 'Minimal 1 item harus diisi.',
        'details.*.actual_qty.required' => 'Qty aktual wajib diisi.',
        'details.*.actual_qty.min' => 'Qty aktual minimal 0.',
    ];

    public function save()
    {
        $this->business_unit_id = BusinessUnitService::resolveBusinessUnitId($this->business_unit_id);
        $this->validate();

        $service = new StockOpnameService();

        $data = [
            'business_unit_id' => $this->business_unit_id,
            'opname_date' => $this->opname_date,
            'pic_name' => $this->pic_name ?: null,
            'notes' => $this->notes ?: null,
        ];

        // Hanya kirim detail yang qty-nya berbeda
        $detailsToSave = [];
        foreach ($this->details as $detail) {
            $detailsToSave[] = [
                'stock_id' => $detail['stock_id'],
                'actual_qty' => $detail['actual_qty'],
                'notes' => $detail['notes'] ?? null,
            ];
        }

        $service->createStockOpname($data, $detailsToSave);

        $this->dispatch('alert', type: 'success', message: 'Stock opname berhasil dibuat.');
        $this->dispatch('refreshStockOpnameList');
        $this->closeModal();
    }

    public function getUnitsProperty()
    {
        return BusinessUnitService::getAvailableUnits();
    }

    public function render()
    {
        return view('livewire.stock-management.stock-opname-form', [
            'units' => $this->units,
            'isSuperAdmin' => BusinessUnitService::isSuperAdmin(),
        ]);
    }
}
