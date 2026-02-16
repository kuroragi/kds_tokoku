@extends('pdf.layout')

@section('content')
{{-- Balance Indicators --}}
<table style="width: 100%; margin-bottom: 10px;">
    <tr>
        <td width="33%" style="padding-right: 5px;">
            <div class="summary-box {{ $data['ns_balanced'] ? 'success' : 'danger' }}">
                <strong>Neraca Saldo:</strong> {{ $data['ns_balanced'] ? 'SEIMBANG' : 'TIDAK SEIMBANG' }}
            </div>
        </td>
        <td width="33%" style="padding: 0 5px;">
            <div class="summary-box {{ $data['adj_balanced'] ? 'success' : 'danger' }}">
                <strong>Penyesuaian:</strong> {{ $data['adj_balanced'] ? 'SEIMBANG' : 'TIDAK SEIMBANG' }}
            </div>
        </td>
        <td width="33%" style="padding-left: 5px;">
            <div class="summary-box {{ $data['nsd_balanced'] ? 'success' : 'danger' }}">
                <strong>NS Disesuaikan:</strong> {{ $data['nsd_balanced'] ? 'SEIMBANG' : 'TIDAK SEIMBANG' }}
            </div>
        </td>
    </tr>
</table>

<table class="report-table">
    <thead>
        <tr>
            <th width="6%" rowspan="2" style="vertical-align: middle;">Kode</th>
            <th width="18%" rowspan="2" style="vertical-align: middle;">Nama Akun</th>
            <th width="12.5%" colspan="2" class="text-center" style="border-bottom: 1px solid rgba(255,255,255,0.3);">Neraca Saldo</th>
            <th width="12.5%" colspan="2" class="text-center" style="border-bottom: 1px solid rgba(255,255,255,0.3);">Penyesuaian</th>
            <th width="12.5%" colspan="2" class="text-center" style="border-bottom: 1px solid rgba(255,255,255,0.3);">NS Disesuaikan</th>
        </tr>
        <tr>
            <th class="text-right" style="font-size: 8px;">Debit</th>
            <th class="text-right" style="font-size: 8px;">Kredit</th>
            <th class="text-right" style="font-size: 8px;">Debit</th>
            <th class="text-right" style="font-size: 8px;">Kredit</th>
            <th class="text-right" style="font-size: 8px;">Debit</th>
            <th class="text-right" style="font-size: 8px;">Kredit</th>
        </tr>
    </thead>
    <tbody>
        @forelse($data['accounts'] as $account)
        <tr>
            <td class="text-primary fw-bold" style="font-size: 8px;">{{ $account->coa_code }}</td>
            <td style="font-size: 8px;">{{ $account->coa_name }}</td>
            {{-- NS --}}
            <td class="text-right" style="font-size: 8px;">
                {{ $account->ns_debit > 0 ? number_format($account->ns_debit, 0, ',', '.') : '-' }}
            </td>
            <td class="text-right" style="font-size: 8px;">
                {{ $account->ns_credit > 0 ? number_format($account->ns_credit, 0, ',', '.') : '-' }}
            </td>
            {{-- Adj --}}
            <td class="text-right" style="font-size: 8px;">
                {{ $account->adj_debit > 0 ? number_format($account->adj_debit, 0, ',', '.') : '-' }}
            </td>
            <td class="text-right" style="font-size: 8px;">
                {{ $account->adj_credit > 0 ? number_format($account->adj_credit, 0, ',', '.') : '-' }}
            </td>
            {{-- NSD --}}
            <td class="text-right" style="font-size: 8px;">
                {{ $account->nsd_debit > 0 ? number_format($account->nsd_debit, 0, ',', '.') : '-' }}
            </td>
            <td class="text-right" style="font-size: 8px;">
                {{ $account->nsd_credit > 0 ? number_format($account->nsd_credit, 0, ',', '.') : '-' }}
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="8" class="text-center text-muted">Tidak ada data.</td>
        </tr>
        @endforelse
    </tbody>
    @if($data['accounts']->count() > 0)
    <tfoot>
        <tr class="total-row">
            <td colspan="2" class="text-right">TOTAL</td>
            <td class="text-right" style="font-size: 8px;">{{ number_format($data['total_ns_debit'], 0, ',', '.') }}</td>
            <td class="text-right" style="font-size: 8px;">{{ number_format($data['total_ns_credit'], 0, ',', '.') }}</td>
            <td class="text-right" style="font-size: 8px;">{{ number_format($data['total_adj_debit'], 0, ',', '.') }}</td>
            <td class="text-right" style="font-size: 8px;">{{ number_format($data['total_adj_credit'], 0, ',', '.') }}</td>
            <td class="text-right" style="font-size: 8px;">{{ number_format($data['total_nsd_debit'], 0, ',', '.') }}</td>
            <td class="text-right" style="font-size: 8px;">{{ number_format($data['total_nsd_credit'], 0, ',', '.') }}</td>
        </tr>
    </tfoot>
    @endif
</table>
@endsection
