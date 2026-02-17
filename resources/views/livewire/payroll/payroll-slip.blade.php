<div>
    <div wire:ignore.self class="modal fade" id="payrollSlipModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="ri-file-text-line me-1"></i> Slip Gaji</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0" id="slip-gaji-content">
                    @if($entry)
                    <div class="p-4">
                        {{-- Header --}}
                        <div class="text-center mb-4">
                            <h5 class="fw-bold mb-1">SLIP GAJI</h5>
                            <p class="text-muted mb-0">{{ $entry->payrollPeriod->name ?? '' }}</p>
                            <p class="text-muted small">{{ $entry->employee->businessUnit->name ?? '' }}</p>
                        </div>

                        {{-- Employee Info --}}
                        <div class="row mb-4">
                            <div class="col-6">
                                <table class="table table-sm table-borderless mb-0">
                                    <tr>
                                        <td class="text-muted small" width="120">Nama</td>
                                        <td class="fw-medium">{{ $entry->employee->name ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted small">NIK</td>
                                        <td>{{ $entry->employee->code ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted small">Jabatan</td>
                                        <td>{{ $entry->employee->position->name ?? '-' }}</td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-6">
                                <table class="table table-sm table-borderless mb-0">
                                    <tr>
                                        <td class="text-muted small" width="120">NPWP</td>
                                        <td>{{ $entry->employee->npwp ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted small">Status PTKP</td>
                                        <td>{{ $entry->employee->ptkp_status ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted small">No. Rekening</td>
                                        <td>{{ $entry->employee->bank_account_number ? $entry->employee->bank_name . ' - ' . $entry->employee->bank_account_number : '-' }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <hr>

                        {{-- Details --}}
                        <div class="row g-4">
                            {{-- Pendapatan --}}
                            <div class="col-md-6">
                                <h6 class="fw-bold text-primary border-bottom pb-2 mb-2">
                                    <i class="ri-add-circle-line me-1"></i> PENDAPATAN
                                </h6>
                                <table class="table table-sm table-borderless mb-0">
                                    @php $totalEarnings = 0; @endphp
                                    @foreach($earnings as $detail)
                                    <tr>
                                        <td class="small py-1">{{ $detail->component_name }}</td>
                                        <td class="small py-1 text-end">Rp {{ number_format($detail->amount) }}</td>
                                    </tr>
                                    @php $totalEarnings += $detail->amount; @endphp
                                    @endforeach
                                    <tr class="border-top">
                                        <td class="fw-bold small py-1">Total Pendapatan</td>
                                        <td class="fw-bold small py-1 text-end">Rp {{ number_format($totalEarnings) }}</td>
                                    </tr>
                                </table>

                                {{-- Tunjangan Perusahaan --}}
                                @if($benefits->count() > 0)
                                <h6 class="fw-bold text-info border-bottom pb-2 mb-2 mt-3">
                                    <i class="ri-shield-check-line me-1"></i> TUNJANGAN PERUSAHAAN
                                </h6>
                                <table class="table table-sm table-borderless mb-0">
                                    @php $totalBenefits = 0; @endphp
                                    @foreach($benefits as $detail)
                                    <tr>
                                        <td class="small py-1">{{ $detail->component_name }}</td>
                                        <td class="small py-1 text-end">Rp {{ number_format($detail->amount) }}</td>
                                    </tr>
                                    @php $totalBenefits += $detail->amount; @endphp
                                    @endforeach
                                    <tr class="border-top">
                                        <td class="fw-bold small py-1">Total Tunjangan</td>
                                        <td class="fw-bold small py-1 text-end">Rp {{ number_format($totalBenefits) }}</td>
                                    </tr>
                                </table>
                                @endif
                            </div>

                            {{-- Potongan --}}
                            <div class="col-md-6">
                                <h6 class="fw-bold text-danger border-bottom pb-2 mb-2">
                                    <i class="ri-subtract-line me-1"></i> POTONGAN
                                </h6>
                                <table class="table table-sm table-borderless mb-0">
                                    @php $totalDeductions = 0; @endphp
                                    @foreach($deductions as $detail)
                                    <tr>
                                        <td class="small py-1">{{ $detail->component_name }}</td>
                                        <td class="small py-1 text-end">Rp {{ number_format($detail->amount) }}</td>
                                    </tr>
                                    @php $totalDeductions += $detail->amount; @endphp
                                    @endforeach

                                    {{-- PPh21 --}}
                                    @if($entry->pph21_amount > 0)
                                    <tr>
                                        <td class="small py-1">
                                            PPh21
                                            @if($entry->pph21_rate > 0)
                                            <span class="text-muted">({{ number_format($entry->pph21_rate, 2) }}%)</span>
                                            @endif
                                        </td>
                                        <td class="small py-1 text-end">Rp {{ number_format($entry->pph21_amount) }}</td>
                                    </tr>
                                    @php $totalDeductions += $entry->pph21_amount; @endphp
                                    @endif

                                    <tr class="border-top">
                                        <td class="fw-bold small py-1">Total Potongan</td>
                                        <td class="fw-bold small py-1 text-end text-danger">Rp {{ number_format($totalDeductions) }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <hr>

                        {{-- Summary --}}
                        <div class="bg-success bg-opacity-10 rounded-3 p-3 mt-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-sm table-borderless mb-0">
                                        <tr>
                                            <td class="small text-muted">Gaji Bruto</td>
                                            <td class="small text-end">Rp {{ number_format($entry->gross_salary) }}</td>
                                        </tr>
                                        <tr>
                                            <td class="small text-muted">Total Potongan</td>
                                            <td class="small text-end text-danger">- Rp {{ number_format($entry->total_deductions + $entry->pph21_amount) }}</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6 d-flex align-items-center justify-content-end">
                                    <div class="text-end">
                                        <div class="small text-muted">GAJI BERSIH (Take Home Pay)</div>
                                        <div class="fs-4 fw-bold text-success">Rp {{ number_format($entry->net_salary) }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Footer --}}
                        <div class="mt-4 text-center">
                            <small class="text-muted">
                                Dicetak pada {{ now()->format('d/m/Y H:i') }}
                                — Slip gaji ini digenerate otomatis oleh sistem
                            </small>
                        </div>
                    </div>
                    @else
                    <div class="text-center py-5 text-muted">
                        <p>Tidak ada data slip gaji.</p>
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-primary" onclick="printSlip()">
                        <i class="ri-printer-line me-1"></i> Cetak
                    </button>
                </div>
            </div>
        </div>
    </div>

    @script
    <script>
        $wire.on('showPayrollSlipModal', () => {
            new bootstrap.Modal(document.getElementById('payrollSlipModal')).show();
        });

        window.printSlip = function() {
            const content = document.getElementById('slip-gaji-content');
            if (!content) return;

            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Slip Gaji</title>
                    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
                    <style>
                        body { padding: 20px; font-size: 12px; }
                        @media print { body { padding: 0; } }
                    </style>
                </head>
                <body>${content.innerHTML}</body>
                </html>
            `);
            printWindow.document.close();
            printWindow.onload = function() {
                printWindow.print();
            };
        };
    </script>
    @endscript
</div>
