@extends('pdf.layout')

@section('content')
{{-- ===== SECTION 1: NERACA (Balance Sheet) ===== --}}
<div class="section-title aktiva" style="font-size: 13px; margin-bottom: 8px;">
    I. NERACA (BALANCE SHEET)
</div>

{{-- Balance Status --}}
<div class="summary-box {{ $balanceSheet['is_balanced'] ? 'success' : 'danger' }}">
    <strong>Status Neraca:</strong>
    @if($balanceSheet['is_balanced'])
        SEIMBANG — Aktiva: {{ number_format($balanceSheet['total_aktiva'], 0, ',', '.') }} = Pasiva+Modal+L/R: {{ number_format($balanceSheet['total_pasiva_modal_laba'], 0, ',', '.') }}
    @else
        TIDAK SEIMBANG — Aktiva: {{ number_format($balanceSheet['total_aktiva'], 0, ',', '.') }} ≠ Pasiva+Modal+L/R: {{ number_format($balanceSheet['total_pasiva_modal_laba'], 0, ',', '.') }}
    @endif
</div>

<table class="two-column">
    <tr>
        {{-- LEFT: AKTIVA --}}
        <td style="padding-right: 10px;">
            <div class="section-title aktiva">AKTIVA (Harta)</div>
            <table class="report-table">
                <thead>
                    <tr>
                        <th width="25%">Kode</th>
                        <th width="45%">Nama Akun</th>
                        <th width="30%" class="text-right">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($balanceSheet['aktiva'] as $account)
                    <tr>
                        <td class="text-primary fw-bold">{{ $account->coa_code }}</td>
                        <td>{{ $account->coa_name }}</td>
                        <td class="text-right">{{ number_format($account->saldo, 0, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="text-center text-muted">Tidak ada data</td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="subtotal-row">
                        <td colspan="2" class="text-right">Total Aktiva</td>
                        <td class="text-right fw-bold">{{ number_format($balanceSheet['total_aktiva'], 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </td>

        {{-- RIGHT: PASIVA + MODAL + L/R --}}
        <td style="padding-left: 10px;">
            {{-- Pasiva --}}
            <div class="section-title pasiva">PASIVA (Kewajiban)</div>
            <table class="report-table">
                <thead>
                    <tr>
                        <th width="25%">Kode</th>
                        <th width="45%">Nama Akun</th>
                        <th width="30%" class="text-right">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($balanceSheet['pasiva'] as $account)
                    <tr>
                        <td class="text-primary fw-bold">{{ $account->coa_code }}</td>
                        <td>{{ $account->coa_name }}</td>
                        <td class="text-right">{{ number_format($account->saldo, 0, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="text-center text-muted">Tidak ada data</td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="subtotal-row">
                        <td colspan="2" class="text-right">Total Pasiva</td>
                        <td class="text-right fw-bold">{{ number_format($balanceSheet['total_pasiva'], 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>

            {{-- Modal --}}
            <div class="section-title modal">MODAL (Ekuitas)</div>
            <table class="report-table">
                <thead>
                    <tr>
                        <th width="25%">Kode</th>
                        <th width="45%">Nama Akun</th>
                        <th width="30%" class="text-right">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($balanceSheet['modal'] as $account)
                    <tr>
                        <td class="text-primary fw-bold">{{ $account->coa_code }}</td>
                        <td>{{ $account->coa_name }}</td>
                        <td class="text-right">{{ number_format($account->saldo, 0, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="text-center text-muted">Tidak ada data</td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="subtotal-row">
                        <td colspan="2" class="text-right">Total Modal</td>
                        <td class="text-right fw-bold">{{ number_format($balanceSheet['total_modal'], 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>

            {{-- Laba/Rugi --}}
            <div class="section-title {{ $balanceSheet['laba_rugi'] >= 0 ? 'pendapatan' : 'beban' }}">
                {{ $balanceSheet['laba_rugi'] >= 0 ? 'LABA BERSIH' : 'RUGI BERSIH' }}
            </div>
            <table class="report-table">
                <tbody>
                    <tr class="subtotal-row">
                        <td width="70%">
                            {{ $balanceSheet['laba_rugi'] >= 0 ? 'Laba Bersih Periode Ini' : 'Rugi Bersih Periode Ini' }}
                        </td>
                        <td width="30%" class="text-right fw-bold {{ $balanceSheet['laba_rugi'] >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ number_format(abs($balanceSheet['laba_rugi']), 0, ',', '.') }}
                        </td>
                    </tr>
                </tbody>
            </table>

            {{-- Grand Total --}}
            <table class="report-table">
                <tfoot>
                    <tr class="total-row">
                        <td width="70%" class="text-right">Total Pasiva + Modal + L/R</td>
                        <td width="30%" class="text-right">{{ number_format($balanceSheet['total_pasiva_modal_laba'], 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </td>
    </tr>
</table>

{{-- ===== SECTION 2: LABA RUGI (Income Statement) ===== --}}
<div style="page-break-before: auto; margin-top: 20px;"></div>

<div class="section-title pendapatan" style="font-size: 13px; margin-bottom: 8px;">
    II. LAPORAN LABA RUGI (INCOME STATEMENT)
</div>

<table class="two-column">
    <tr>
        {{-- LEFT: PENDAPATAN --}}
        <td style="padding-right: 10px;">
            <div class="section-title pendapatan">PENDAPATAN</div>
            <table class="report-table">
                <thead>
                    <tr>
                        <th width="25%">Kode</th>
                        <th width="45%">Nama Akun</th>
                        <th width="30%" class="text-right">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($incomeStatement['pendapatan'] as $account)
                    <tr>
                        <td class="text-primary fw-bold">{{ $account->coa_code }}</td>
                        <td>{{ $account->coa_name }}</td>
                        <td class="text-right">{{ number_format($account->saldo, 0, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="text-center text-muted">Tidak ada data</td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="subtotal-row">
                        <td colspan="2" class="text-right">Total Pendapatan</td>
                        <td class="text-right fw-bold">{{ number_format($incomeStatement['total_pendapatan'], 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </td>

        {{-- RIGHT: BEBAN --}}
        <td style="padding-left: 10px;">
            <div class="section-title beban">BEBAN</div>
            <table class="report-table">
                <thead>
                    <tr>
                        <th width="25%">Kode</th>
                        <th width="45%">Nama Akun</th>
                        <th width="30%" class="text-right">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($incomeStatement['beban'] as $account)
                    <tr>
                        <td class="text-primary fw-bold">{{ $account->coa_code }}</td>
                        <td>{{ $account->coa_name }}</td>
                        <td class="text-right">{{ number_format($account->saldo, 0, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="text-center text-muted">Tidak ada data</td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="subtotal-row">
                        <td colspan="2" class="text-right">Total Beban</td>
                        <td class="text-right fw-bold">{{ number_format($incomeStatement['total_beban'], 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </td>
    </tr>
</table>

{{-- Net Income Summary --}}
<div class="summary-box {{ $incomeStatement['is_profit'] ? 'success' : 'danger' }}" style="margin-top: 10px;">
    <table style="width: 100%;">
        <tr>
            <td width="33%" class="text-center">
                <small>Total Pendapatan</small><br>
                <strong>{{ number_format($incomeStatement['total_pendapatan'], 0, ',', '.') }}</strong>
            </td>
            <td width="5%" class="text-center">—</td>
            <td width="33%" class="text-center">
                <small>Total Beban</small><br>
                <strong>{{ number_format($incomeStatement['total_beban'], 0, ',', '.') }}</strong>
            </td>
            <td width="5%" class="text-center">=</td>
            <td width="24%" class="text-center">
                <small>{{ $incomeStatement['is_profit'] ? 'Laba Bersih' : 'Rugi Bersih' }}</small><br>
                <strong style="font-size: 14px;">{{ number_format(abs($incomeStatement['net_income']), 0, ',', '.') }}</strong>
            </td>
        </tr>
    </table>
</div>

{{-- ===== SECTION 3: PERSAMAAN AKUNTANSI ===== --}}
<div style="margin-top: 15px;">
    <div class="section-title modal" style="font-size: 13px; margin-bottom: 8px;">
        III. RINGKASAN PERSAMAAN AKUNTANSI
    </div>

    <div class="summary-box {{ $balanceSheet['is_balanced'] ? 'success' : 'danger' }}">
        <table style="width: 100%;">
            <tr>
                <td width="22%" class="text-center">
                    <small>Aktiva</small><br>
                    <strong>{{ number_format($balanceSheet['total_aktiva'], 0, ',', '.') }}</strong>
                </td>
                <td width="5%" class="text-center"><strong>=</strong></td>
                <td width="22%" class="text-center">
                    <small>Kewajiban</small><br>
                    <strong>{{ number_format($balanceSheet['total_pasiva'], 0, ',', '.') }}</strong>
                </td>
                <td width="5%" class="text-center"><strong>+</strong></td>
                <td width="22%" class="text-center">
                    <small>Ekuitas</small><br>
                    <strong>{{ number_format($balanceSheet['total_modal'], 0, ',', '.') }}</strong>
                </td>
                <td width="5%" class="text-center"><strong>+</strong></td>
                <td width="19%" class="text-center">
                    <small>{{ $incomeStatement['is_profit'] ? 'Laba' : 'Rugi' }}</small><br>
                    <strong>{{ number_format(abs($incomeStatement['net_income']), 0, ',', '.') }}</strong>
                </td>
            </tr>
        </table>
    </div>
</div>
@endsection
