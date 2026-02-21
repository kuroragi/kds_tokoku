<?php

namespace App\Livewire\StockManagement;

use App\Models\SaldoProvider;
use App\Models\Stock;
use App\Services\BusinessUnitService;
use Livewire\Component;

class WarehouseMonitor extends Component
{
    public $business_unit_id = '';
    public $tab = 'stock'; // stock | saldo

    protected $listeners = ['refreshWarehouseMonitor' => '$refresh'];

    public function mount()
    {
        $this->business_unit_id = BusinessUnitService::getDefaultBusinessUnitId();
    }

    public function getIsSuperAdminProperty(): bool
    {
        return BusinessUnitService::isSuperAdmin();
    }

    public function getUnitsProperty()
    {
        return BusinessUnitService::getAvailableUnits();
    }

    public function getLowStockItemsProperty()
    {
        $query = Stock::active()
            ->lowStock()
            ->where('min_stock', '>', 0)
            ->with('businessUnit', 'categoryGroup', 'unitOfMeasure');

        if ($this->business_unit_id) {
            $query->byBusinessUnit($this->business_unit_id);
        }

        return $query->orderByRaw('current_stock - min_stock ASC')->get();
    }

    public function getLowBalanceSaldosProperty()
    {
        $query = SaldoProvider::active()
            ->lowBalance()
            ->with('businessUnit');

        if ($this->business_unit_id) {
            $query->byBusinessUnit($this->business_unit_id);
        }

        return $query->orderByRaw('current_balance - min_balance ASC')->get();
    }

    public function getStockSummaryProperty(): array
    {
        $query = Stock::active()->where('min_stock', '>', 0);
        if ($this->business_unit_id) {
            $query->byBusinessUnit($this->business_unit_id);
        }

        $total = (clone $query)->count();
        $low = (clone $query)->lowStock()->count();
        $outOfStock = (clone $query)->where('current_stock', '<=', 0)->count();

        return [
            'total' => $total,
            'low' => $low,
            'out_of_stock' => $outOfStock,
            'normal' => $total - $low,
        ];
    }

    public function getSaldoSummaryProperty(): array
    {
        $query = SaldoProvider::active()->where('min_balance', '>', 0);
        if ($this->business_unit_id) {
            $query->byBusinessUnit($this->business_unit_id);
        }

        $total = (clone $query)->count();
        $low = (clone $query)->lowBalance()->count();

        return [
            'total' => $total,
            'low' => $low,
            'normal' => $total - $low,
        ];
    }

    public function render()
    {
        return view('livewire.stock-management.warehouse-monitor');
    }
}
