@extends('pdf.layout')

@section('content')
{{-- Net Income Indicator --}}
<div class="summary-box {{ $data['is_profit'] ? 'success' : 'danger' }}">
    <strong>{{ $data['is_profit'] ? 'LABA BERSIH' : 'RUGI BERSIH' }}:</strong>
    Rp {{ number_format(abs($data['net_income']), 0, ',', '.') }}
</div>

<table class="two-column">
    <tr>
        {{-- LEFT: PENDAPATAN --}}
        <td style="padding-right: 10px;">
            <div class="section-title pendapatan">PENDAPATAN</div>
            <table class="report-table">
                <thead>
                    <tr>
                        <th width="20%">Kode</th>
                        <th width="45%">Nama Akun</th>
                        <th width="35%" class="text-right">Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data['pendapatan'] as $account)
                    <tr>
                        <td class="text-primary fw-bold">{{ $account->coa_code }}</td>
                        <td>{{ $account->coa_name }}</td>
                        <td class="text-right text-success">{{ number_format($account->saldo, 0, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="text-center text-muted">Tidak ada data pendapatan</td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="subtotal-row">
                        <td colspan="2" class="text-right">Total Pendapatan</td>
                        <td class="text-right fw-bold text-success">{{ number_format($data['total_pendapatan'], 0, ',', '.') }}</td>
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
                        <th width="20%">Kode</th>
                        <th width="45%">Nama Akun</th>
                        <th width="35%" class="text-right">Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data['beban'] as $account)
                    <tr>
                        <td class="text-primary fw-bold">{{ $account->coa_code }}</td>
                        <td>{{ $account->coa_name }}</td>
                        <td class="text-right text-danger">{{ number_format($account->saldo, 0, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="text-center text-muted">Tidak ada data beban</td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="subtotal-row">
                        <td colspan="2" class="text-right">Total Beban</td>
                        <td class="text-right fw-bold text-danger">{{ number_format($data['total_beban'], 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </td>
    </tr>
</table>

{{-- Summary --}}
<table class="report-table" style="margin-top: 15px;">
    <tbody>
        <tr class="subtotal-row">
            <td width="40%">Total Pendapatan</td>
            <td width="30%" class="text-right text-success fw-bold">{{ number_format($data['total_pendapatan'], 0, ',', '.') }}</td>
            <td width="30%"></td>
        </tr>
        <tr class="subtotal-row">
            <td>Total Beban</td>
            <td class="text-right text-danger fw-bold">({{ number_format($data['total_beban'], 0, ',', '.') }})</td>
            <td></td>
        </tr>
    </tbody>
    <tfoot>
        <tr class="total-row">
            <td>{{ $data['is_profit'] ? 'LABA BERSIH' : 'RUGI BERSIH' }}</td>
            <td></td>
            <td class="text-right">Rp {{ number_format(abs($data['net_income']), 0, ',', '.') }}</td>
        </tr>
    </tfoot>
</table>
@endsection
