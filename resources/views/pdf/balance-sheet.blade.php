@extends('pdf.layout')

@section('content')
{{-- Balance Status --}}
<div class="summary-box {{ $data['is_balanced'] ? 'success' : 'danger' }}">
    <strong>Status Neraca:</strong>
    @if($data['is_balanced'])
        SEIMBANG — Aktiva: {{ number_format($data['total_aktiva'], 0, ',', '.') }} = Pasiva+Modal+L/R: {{ number_format($data['total_pasiva_modal_laba'], 0, ',', '.') }}
    @else
        TIDAK SEIMBANG — Aktiva: {{ number_format($data['total_aktiva'], 0, ',', '.') }} ≠ Pasiva+Modal+L/R: {{ number_format($data['total_pasiva_modal_laba'], 0, ',', '.') }}
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
                    @forelse($data['aktiva'] as $account)
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
                        <td class="text-right fw-bold">{{ number_format($data['total_aktiva'], 0, ',', '.') }}</td>
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
                    @forelse($data['pasiva'] as $account)
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
                        <td class="text-right fw-bold">{{ number_format($data['total_pasiva'], 0, ',', '.') }}</td>
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
                    @forelse($data['modal'] as $account)
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
                        <td class="text-right fw-bold">{{ number_format($data['total_modal'], 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>

            {{-- Laba/Rugi --}}
            <div class="section-title {{ $data['laba_rugi'] >= 0 ? 'pendapatan' : 'beban' }}">
                {{ $data['laba_rugi'] >= 0 ? 'LABA BERSIH' : 'RUGI BERSIH' }}
            </div>
            <table class="report-table">
                <tbody>
                    <tr class="subtotal-row">
                        <td width="70%">
                            {{ $data['laba_rugi'] >= 0 ? 'Laba Bersih Periode Ini' : 'Rugi Bersih Periode Ini' }}
                        </td>
                        <td width="30%" class="text-right fw-bold {{ $data['laba_rugi'] >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ number_format(abs($data['laba_rugi']), 0, ',', '.') }}
                        </td>
                    </tr>
                </tbody>
            </table>

            {{-- Grand Total --}}
            <table class="report-table">
                <tfoot>
                    <tr class="total-row">
                        <td width="70%" class="text-right">Total Pasiva + Modal + L/R</td>
                        <td width="30%" class="text-right">{{ number_format($data['total_pasiva_modal_laba'], 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </td>
    </tr>
</table>
@endsection
