<div>
    {{-- ─── Filters ─── --}}
    <div class="row mb-3">
        <div class="col-md-8">
            <div class="d-flex gap-2 align-items-end">
                @if($isSuperAdmin)
                <div>
                    <label class="form-label small text-muted mb-1">Unit Usaha</label>
                    <select class="form-select form-select-sm" wire:model.live="filterUnit" style="width: 180px;">
                        <option value="">Semua Unit</option>
                        @foreach($units as $unit)
                        <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div>
                    <label class="form-label small text-muted mb-1">Dari</label>
                    <input type="date" class="form-control form-control-sm" wire:model.live.debounce.500ms="startDate" style="width: 160px;">
                </div>
                <div>
                    <label class="form-label small text-muted mb-1">Sampai</label>
                    <input type="date" class="form-control form-control-sm" wire:model.live.debounce.500ms="endDate" style="width: 160px;">
                </div>
                <div>
                    <button class="btn btn-outline-primary btn-sm" wire:click="loadData">
                        <i class="ri-refresh-line"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    @php
        $cards = $data['summary_cards'] ?? [];
        $cashflow = $data['cashflow'] ?? [];
        $pr = $data['payable_receivable'] ?? [];
        $topProducts = $data['top_products'] ?? [];
        $lowStock = $data['low_stock'] ?? [];
        $recentTx = $data['recent_transactions'] ?? [];
        $bankBal = $data['bank_balances'] ?? [];
        $salesChart = $data['sales_chart'] ?? [];
    @endphp

    {{-- ─── Summary Cards ─── --}}
    <div class="row">
        <div class="col-xxl-3 col-sm-6">
            <div class="card widget-flat text-bg-pink">
                <div class="card-body">
                    <div class="float-end"><i class="ri-shopping-cart-line widget-icon"></i></div>
                    <h6 class="text-uppercase mt-0">Total Penjualan</h6>
                    <h2 class="my-2">Rp {{ number_format($cards['total_sales'] ?? 0, 0, ',', '.') }}</h2>
                    <p class="mb-0">
                        @if(($cards['sales_growth'] ?? 0) > 0)
                        <span class="badge bg-white bg-opacity-25 me-1"><i class="ri-arrow-up-s-line"></i> {{ $cards['sales_growth'] }}%</span>
                        @elseif(($cards['sales_growth'] ?? 0) < 0)
                        <span class="badge bg-white bg-opacity-25 me-1"><i class="ri-arrow-down-s-line"></i> {{ $cards['sales_growth'] }}%</span>
                        @else
                        <span class="badge bg-white bg-opacity-10 me-1">—</span>
                        @endif
                        <span class="text-nowrap">vs Periode Sebelumnya</span>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-sm-6">
            <div class="card widget-flat text-bg-purple">
                <div class="card-body">
                    <div class="float-end"><i class="ri-shopping-bag-line widget-icon"></i></div>
                    <h6 class="text-uppercase mt-0">Total Pembelian</h6>
                    <h2 class="my-2">Rp {{ number_format($cards['total_purchase'] ?? 0, 0, ',', '.') }}</h2>
                    <p class="mb-0">
                        <span class="badge bg-white bg-opacity-10 me-1">{{ $cards['sale_count'] ?? 0 }} transaksi</span>
                        <span class="text-nowrap">Periode ini</span>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-sm-6">
            <div class="card widget-flat text-bg-info">
                <div class="card-body">
                    <div class="float-end"><i class="ri-wallet-3-line widget-icon"></i></div>
                    <h6 class="text-uppercase mt-0">Laba Kotor</h6>
                    <h2 class="my-2">Rp {{ number_format(($cashflow['net'] ?? 0), 0, ',', '.') }}</h2>
                    <p class="mb-0">
                        <span class="badge bg-white bg-opacity-25 me-1">Penjualan - Pembelian</span>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-sm-6">
            <div class="card widget-flat text-bg-primary">
                <div class="card-body">
                    <div class="float-end"><i class="ri-user-add-line widget-icon"></i></div>
                    <h6 class="text-uppercase mt-0">Customer Baru</h6>
                    <h2 class="my-2">{{ $cards['new_customers'] ?? 0 }}</h2>
                    <p class="mb-0">
                        <span class="badge bg-white bg-opacity-10 me-1">Periode ini</span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── Charts Row ─── --}}
    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="header-title mb-3"><i class="ri-bar-chart-grouped-line text-primary me-1"></i> Grafik Penjualan & Pembelian</h5>
                    <div id="sales-purchase-chart" style="min-height: 320px;"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            {{-- Cash Flow --}}
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="header-title mb-3"><i class="ri-exchange-funds-line text-success me-1"></i> Cash Flow</h5>
                    <div class="list-group list-group-flush">
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 border-0">
                            <span class="text-success"><i class="ri-arrow-up-line"></i> Pemasukan</span>
                            <strong class="text-success">Rp {{ number_format($cashflow['income'] ?? 0, 0, ',', '.') }}</strong>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 border-0">
                            <span class="text-danger"><i class="ri-arrow-down-line"></i> Pengeluaran</span>
                            <strong class="text-danger">Rp {{ number_format($cashflow['expense'] ?? 0, 0, ',', '.') }}</strong>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 border-top">
                            <span class="text-primary fw-semibold"><i class="ri-wallet-line"></i> Saldo Bersih</span>
                            <strong class="text-primary">Rp {{ number_format($cashflow['net'] ?? 0, 0, ',', '.') }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Payable / Receivable --}}
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="header-title mb-3"><i class="ri-exchange-dollar-line text-warning me-1"></i> Hutang & Piutang</h5>
                    <div class="row text-center">
                        <div class="col-6 border-end">
                            <p class="text-muted mb-1 small">Hutang Usaha</p>
                            <h5 class="text-danger mb-0">Rp {{ number_format($pr['payable_total'] ?? 0, 0, ',', '.') }}</h5>
                            <small class="text-muted">{{ $pr['payable_count'] ?? 0 }} invoice</small>
                            @if(($pr['payable_overdue'] ?? 0) > 0)
                            <br><span class="badge bg-danger-subtle text-danger">{{ $pr['payable_overdue'] }} jatuh tempo</span>
                            @endif
                        </div>
                        <div class="col-6">
                            <p class="text-muted mb-1 small">Piutang Usaha</p>
                            <h5 class="text-success mb-0">Rp {{ number_format($pr['receivable_total'] ?? 0, 0, ',', '.') }}</h5>
                            <small class="text-muted">{{ $pr['receivable_count'] ?? 0 }} invoice</small>
                            @if(($pr['receivable_overdue'] ?? 0) > 0)
                            <br><span class="badge bg-warning-subtle text-warning">{{ $pr['receivable_overdue'] }} jatuh tempo</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── Middle Row: Bank Balances + Top Products ─── --}}
    <div class="row">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="header-title mb-3"><i class="ri-bank-line text-primary me-1"></i> Saldo Kas & Bank</h5>
                    @if(count($bankBal) > 0)
                    <div class="list-group list-group-flush">
                        @foreach($bankBal as $bb)
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <div>
                                <i class="{{ $bb['type'] === 'bank' ? 'ri-bank-card-line text-primary' : 'ri-money-dollar-circle-line text-success' }} me-1"></i>
                                <span class="small">{{ $bb['name'] }}</span>
                            </div>
                            <strong class="small">Rp {{ number_format($bb['balance'], 0, ',', '.') }}</strong>
                        </div>
                        @endforeach
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between fw-bold">
                        <span>Total</span>
                        <span>Rp {{ number_format(array_sum(array_column($bankBal, 'balance')), 0, ',', '.') }}</span>
                    </div>
                    @else
                    <p class="text-muted text-center py-3 mb-0">Belum ada data kas/bank.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="header-title mb-3"><i class="ri-trophy-line text-warning me-1"></i> Produk Terlaris</h5>
                    @if(count($topProducts) > 0)
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Produk</th>
                                    <th class="text-end">Qty</th>
                                    <th class="text-end">Nominal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topProducts as $idx => $tp)
                                <tr>
                                    <td class="text-muted">{{ $idx + 1 }}</td>
                                    <td>
                                        <span class="fw-semibold small">{{ $tp->name ?? ($tp['name'] ?? '-') }}</span><br>
                                        <small class="text-muted">{{ $tp->code ?? ($tp['code'] ?? '-') }}</small>
                                    </td>
                                    <td class="text-end">{{ number_format($tp->total_qty ?? ($tp['total_qty'] ?? 0), 0) }}</td>
                                    <td class="text-end small">Rp {{ number_format($tp->total_amount ?? ($tp['total_amount'] ?? 0), 0, ',', '.') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="text-muted text-center py-3 mb-0">Belum ada data penjualan.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="header-title mb-3"><i class="ri-alert-line text-danger me-1"></i> Stok Menipis</h5>
                    @if(count($lowStock) > 0)
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Produk</th>
                                    <th class="text-end">Stok</th>
                                    <th class="text-end">Min</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($lowStock as $ls)
                                <tr>
                                    <td>
                                        <span class="fw-semibold small">{{ $ls['name'] }}</span><br>
                                        <small class="text-muted">{{ $ls['code'] }}</small>
                                    </td>
                                    <td class="text-end text-danger fw-bold">{{ number_format($ls['current_stock'], 0) }}</td>
                                    <td class="text-end text-muted">{{ number_format($ls['min_stock'], 0) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="text-success text-center py-3 mb-0"><i class="ri-check-double-line"></i> Semua stok aman.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ─── Recent Transactions ─── --}}
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="header-title mb-3"><i class="ri-file-list-3-line text-primary me-1"></i> Transaksi Terbaru</h5>
                    @if(count($recentTx) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>No. Jurnal</th>
                                    <th>Tanggal</th>
                                    <th>Keterangan</th>
                                    <th>Tipe</th>
                                    <th class="text-end">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentTx as $tx)
                                <tr>
                                    <td class="fw-semibold">{{ $tx['journal_no'] }}</td>
                                    <td>{{ $tx['date'] }}</td>
                                    <td>{{ Str::limit($tx['description'], 60) }}</td>
                                    <td>
                                        @php
                                            $typeColors = ['general' => 'primary', 'adjustment' => 'warning', 'tax' => 'info', 'closing' => 'dark', 'opening' => 'success'];
                                        @endphp
                                        <span class="badge bg-{{ $typeColors[$tx['type']] ?? 'secondary' }}">{{ strtoupper($tx['type']) }}</span>
                                    </td>
                                    <td class="text-end">Rp {{ number_format($tx['amount'], 0, ',', '.') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="text-muted text-center py-3 mb-0">Belum ada transaksi.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@script
<script>
    let mainChart = null;

    function renderCharts(data) {
        const salesChart = data?.sales_chart ?? { labels: [], sales: [], purchase: [] };

        // Destroy existing
        if (mainChart) {
            mainChart.destroy();
            mainChart = null;
        }

        const el = document.querySelector('#sales-purchase-chart');
        if (!el) return;

        if (salesChart.labels.length === 0) {
            el.innerHTML = '<div class="text-center text-muted py-5"><i class="ri-bar-chart-line" style="font-size: 48px; color: #ccc;"></i><p class="mt-2">Belum ada data</p></div>';
            return;
        }

        mainChart = new ApexCharts(el, {
            chart: { type: 'area', height: 320, toolbar: { show: false }, fontFamily: 'inherit' },
            series: [
                { name: 'Penjualan', data: salesChart.sales },
                { name: 'Pembelian', data: salesChart.purchase }
            ],
            xaxis: { categories: salesChart.labels },
            yaxis: {
                labels: { formatter: v => 'Rp ' + new Intl.NumberFormat('id-ID').format(v) }
            },
            tooltip: {
                y: { formatter: v => 'Rp ' + new Intl.NumberFormat('id-ID').format(v) }
            },
            colors: ['#3b82f6', '#ef4444'],
            fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.1 } },
            stroke: { curve: 'smooth', width: 2 },
            dataLabels: { enabled: false },
            grid: { borderColor: '#f1f1f1' },
        });
        mainChart.render();
    }

    // Initial render
    $wire.on('dashboard-updated', (event) => {
        setTimeout(() => renderCharts(event[0]?.data ?? event.data ?? event), 100);
    });

    // On page load
    document.addEventListener('DOMContentLoaded', () => {
        setTimeout(() => renderCharts(@json($data)), 200);
    });
</script>
@endscript
