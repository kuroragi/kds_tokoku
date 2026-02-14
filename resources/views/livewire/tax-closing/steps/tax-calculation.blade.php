{{-- Step 2: Perhitungan Pajak --}}
<div class="p-3">
    {{-- Header & Controls --}}
    <div class="d-flex justify-content-between align-items-end mb-3 flex-wrap gap-2">
        <h6 class="fw-bold mb-0">
            <i class="ri-calculator-line text-primary me-2"></i>
            Perhitungan Pajak — {{ $selectedYear }}
        </h6>
        <div class="d-flex gap-2 align-items-end">
            <div>
                <label class="form-label small fw-medium mb-1">Tarif PPh (%)</label>
                <input type="number" class="form-control form-control-sm" wire:model="taxRate" min="0" max="100" step="0.01" style="width: 100px;">
            </div>
            <button type="button" class="btn btn-primary btn-sm" wire:click="calculateTax">
                <i class="ri-calculator-line"></i> Hitung Pajak
            </button>
            @if($showTaxReport)
            <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="clearTaxReport">
                <i class="ri-close-line"></i> Reset
            </button>
            @endif
        </div>
    </div>

    @if($showTaxReport && $calculation)
    {{-- Calculation Worksheet --}}
    <div class="table-responsive">
        <table class="table table-bordered mb-0">
            <thead>
                <tr class="table-dark">
                    <th colspan="2" class="text-center">Perhitungan Pajak Penghasilan Badan Tahun {{ $selectedYear }}</th>
                    <th width="20%" class="text-center">Jumlah (Rp)</th>
                </tr>
            </thead>
            <tbody>
                {{-- Section 1: Laba/Rugi Komersial --}}
                <tr class="table-primary">
                    <td colspan="3" class="fw-bold">
                        <i class="ri-bar-chart-box-line me-1"></i> I. LABA/RUGI KOMERSIAL
                    </td>
                </tr>
                <tr>
                    <td width="5%"></td>
                    <td>Total Pendapatan</td>
                    <td class="text-end">{{ number_format($calculation['commercial_detail']['total_pendapatan'], 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td></td>
                    <td>Total Beban</td>
                    <td class="text-end">({{ number_format($calculation['commercial_detail']['total_beban'], 0, ',', '.') }})</td>
                </tr>
                <tr class="table-light fw-bold">
                    <td></td>
                    <td>{{ $calculation['commercial_profit'] >= 0 ? 'Laba Komersial' : 'Rugi Komersial' }}</td>
                    <td class="text-end {{ $calculation['commercial_profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ number_format($calculation['commercial_profit'], 0, ',', '.') }}
                    </td>
                </tr>

                {{-- Section 2: Koreksi Fiskal --}}
                <tr class="table-primary">
                    <td colspan="3" class="fw-bold">
                        <i class="ri-exchange-line me-1"></i> II. KOREKSI FISKAL
                    </td>
                </tr>
                @if($calculation['fiscal_corrections']['items']->count() > 0)
                    @if($calculation['fiscal_corrections']['items']->where('correction_type', 'positive')->count() > 0)
                    <tr class="table-success-subtle">
                        <td></td>
                        <td colspan="2" class="fw-medium text-success">Koreksi Positif (+)</td>
                    </tr>
                    @foreach($calculation['fiscal_corrections']['items']->where('correction_type', 'positive') as $item)
                    <tr>
                        <td></td>
                        <td class="ps-4">
                            {{ $item->description }}
                            <span class="badge bg-{{ $item->category === 'beda_tetap' ? 'primary' : 'warning' }}-subtle text-{{ $item->category === 'beda_tetap' ? 'primary' : 'warning' }} ms-1">
                                {{ $item->category === 'beda_tetap' ? 'Beda Tetap' : 'Beda Waktu' }}
                            </span>
                        </td>
                        <td class="text-end">{{ number_format($item->amount, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                    <tr class="fw-medium">
                        <td></td>
                        <td class="text-end">Subtotal Koreksi Positif</td>
                        <td class="text-end text-success">{{ number_format($calculation['fiscal_corrections']['total_positive'], 0, ',', '.') }}</td>
                    </tr>
                    @endif

                    @if($calculation['fiscal_corrections']['items']->where('correction_type', 'negative')->count() > 0)
                    <tr class="table-danger-subtle">
                        <td></td>
                        <td colspan="2" class="fw-medium text-danger">Koreksi Negatif (-)</td>
                    </tr>
                    @foreach($calculation['fiscal_corrections']['items']->where('correction_type', 'negative') as $item)
                    <tr>
                        <td></td>
                        <td class="ps-4">
                            {{ $item->description }}
                            <span class="badge bg-{{ $item->category === 'beda_tetap' ? 'primary' : 'warning' }}-subtle text-{{ $item->category === 'beda_tetap' ? 'primary' : 'warning' }} ms-1">
                                {{ $item->category === 'beda_tetap' ? 'Beda Tetap' : 'Beda Waktu' }}
                            </span>
                        </td>
                        <td class="text-end">({{ number_format($item->amount, 0, ',', '.') }})</td>
                    </tr>
                    @endforeach
                    <tr class="fw-medium">
                        <td></td>
                        <td class="text-end">Subtotal Koreksi Negatif</td>
                        <td class="text-end text-danger">({{ number_format($calculation['fiscal_corrections']['total_negative'], 0, ',', '.') }})</td>
                    </tr>
                    @endif
                @else
                <tr>
                    <td></td>
                    <td colspan="2" class="text-muted fst-italic">Tidak ada koreksi fiskal</td>
                </tr>
                @endif

                {{-- Section 3: Laba/Rugi Fiskal --}}
                <tr class="table-primary">
                    <td colspan="3" class="fw-bold">
                        <i class="ri-funds-line me-1"></i> III. LABA/RUGI FISKAL
                    </td>
                </tr>
                <tr class="table-light fw-bold">
                    <td></td>
                    <td>{{ $calculation['fiscal_profit'] >= 0 ? 'Laba Fiskal' : 'Rugi Fiskal' }}</td>
                    <td class="text-end {{ $calculation['fiscal_profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ number_format($calculation['fiscal_profit'], 0, ',', '.') }}
                    </td>
                </tr>

                {{-- Section 4: Kompensasi Rugi --}}
                <tr class="table-primary">
                    <td colspan="3" class="fw-bold">
                        <i class="ri-arrow-left-right-line me-1"></i> IV. KOMPENSASI KERUGIAN
                    </td>
                </tr>
                @if($calculation['loss_compensations']->count() > 0)
                    @foreach($calculation['loss_compensations'] as $lc)
                    <tr>
                        <td></td>
                        <td class="ps-4">
                            Rugi Fiskal Tahun {{ $lc->source_year }}
                            <small class="text-muted">(sisa: Rp {{ number_format($lc->remaining_amount, 0, ',', '.') }}, kadaluarsa: {{ $lc->expires_year }})</small>
                        </td>
                        <td class="text-end">({{ number_format(min($lc->remaining_amount, max(0, $calculation['fiscal_profit'])), 0, ',', '.') }})</td>
                    </tr>
                    @endforeach
                @else
                <tr>
                    <td></td>
                    <td colspan="2" class="text-muted fst-italic">Tidak ada kompensasi kerugian yang tersedia</td>
                </tr>
                @endif
                <tr class="fw-medium">
                    <td></td>
                    <td class="text-end">Total Kompensasi Rugi</td>
                    <td class="text-end">({{ number_format($calculation['loss_compensation_amount'], 0, ',', '.') }})</td>
                </tr>

                {{-- Section 5: PKP --}}
                <tr class="table-primary">
                    <td colspan="3" class="fw-bold">
                        <i class="ri-money-dollar-circle-line me-1"></i> V. PENGHASILAN KENA PAJAK
                    </td>
                </tr>
                <tr class="table-light fw-bold">
                    <td></td>
                    <td>Penghasilan Kena Pajak (PKP)</td>
                    <td class="text-end">{{ number_format($calculation['taxable_income'], 0, ',', '.') }}</td>
                </tr>

                {{-- Section 6: PPh Terutang --}}
                <tr class="table-warning">
                    <td colspan="3" class="fw-bold">
                        <i class="ri-government-line me-1"></i> VI. PAJAK PENGHASILAN TERUTANG
                    </td>
                </tr>
                <tr>
                    <td></td>
                    <td>PKP &times; {{ number_format($calculation['tax_rate'], 2) }}%</td>
                    <td class="text-end">{{ number_format($calculation['taxable_income'], 0, ',', '.') }} &times; {{ number_format($calculation['tax_rate'], 2) }}%</td>
                </tr>
                <tr class="table-warning fw-bold fs-6">
                    <td></td>
                    <td>PPh Badan Terutang</td>
                    <td class="text-end text-danger">Rp {{ number_format($calculation['tax_amount'], 0, ',', '.') }}</td>
                </tr>

                {{-- Section 7: Laba Bersih --}}
                <tr class="table-success">
                    <td colspan="3" class="fw-bold">
                        <i class="ri-hand-coin-line me-1"></i> VII. LABA BERSIH SETELAH PAJAK
                    </td>
                </tr>
                <tr class="table-success fw-bold fs-6">
                    <td></td>
                    <td>Laba Bersih</td>
                    <td class="text-end {{ $calculation['net_income'] >= 0 ? 'text-success' : 'text-danger' }}">
                        Rp {{ number_format($calculation['net_income'], 0, ',', '.') }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Action Buttons --}}
    <div class="p-3 bg-light border-top">
        <div class="d-flex justify-content-end gap-2 flex-wrap">
            @if($savedCalculation)
                <span class="badge bg-{{ $savedCalculation->isFinalized() ? 'success' : 'warning' }} align-self-center me-2">
                    Status: {{ $savedCalculation->isFinalized() ? 'FINAL' : 'DRAFT' }}
                </span>
            @endif

            @if(!$savedCalculation || !$savedCalculation->isFinalized())
                <button type="button" class="btn btn-info" wire:click="saveTaxCalculation">
                    <i class="ri-save-line me-1"></i> Simpan Perhitungan
                </button>
            @endif
        </div>
    </div>

    @elseif($showTaxReport && !$calculation)
    <div class="text-center py-5 text-muted">
        <i class="ri-error-warning-line fs-1 d-block mb-2"></i>
        <p>Tidak dapat menghitung pajak. Pastikan ada data transaksi untuk tahun {{ $selectedYear }}.</p>
    </div>
    @else
    <div class="text-center py-5 text-muted">
        <i class="ri-calculator-line fs-1 d-block mb-2"></i>
        <p>Atur tarif PPh lalu klik "Hitung Pajak" untuk memulai perhitungan.</p>
        @if($savedCalculation)
        <div class="alert alert-info d-inline-block">
            <small>
                <i class="ri-information-line me-1"></i>
                Sudah ada perhitungan tersimpan —
                <span class="badge bg-{{ $savedCalculation->isFinalized() ? 'success' : 'warning' }}">
                    {{ $savedCalculation->isFinalized() ? 'FINAL' : 'DRAFT' }}
                </span>
            </small>
        </div>
        @endif
    </div>
    @endif
</div>
