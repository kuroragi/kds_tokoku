@extends('pdf.layout')

@section('content')
@php
    $typeLabels = [
        'aktiva' => 'Aktiva',
        'pasiva' => 'Pasiva',
        'modal' => 'Modal',
        'pendapatan' => 'Pendapatan',
        'beban' => 'Beban',
    ];
@endphp

<table class="report-table">
    <thead>
        <tr>
            <th width="10%">Kode</th>
            <th width="30%">Nama Akun</th>
            <th width="10%">Tipe</th>
            <th width="10%" class="text-center">Transaksi</th>
            <th width="15%" class="text-right">Total Debit</th>
            <th width="15%" class="text-right">Total Kredit</th>
            <th width="10%" class="text-right">Saldo</th>
        </tr>
    </thead>
    <tbody>
        @forelse($summaryData as $account)
        <tr>
            <td class="text-primary fw-bold">{{ $account->coa_code }}</td>
            <td>{{ $account->coa_name }}</td>
            <td>
                <span class="badge badge-{{ ['aktiva' => 'primary', 'pasiva' => 'warning', 'modal' => 'info', 'pendapatan' => 'success', 'beban' => 'danger'][$account->coa_type] ?? 'primary' }}">
                    {{ $typeLabels[$account->coa_type] ?? ucfirst($account->coa_type) }}
                </span>
            </td>
            <td class="text-center">{{ $account->total_transactions }}</td>
            <td class="text-right">{{ number_format($account->total_debit, 0, ',', '.') }}</td>
            <td class="text-right">{{ number_format($account->total_credit, 0, ',', '.') }}</td>
            <td class="text-right fw-bold">
                @php $bal = $account->total_debit - $account->total_credit; @endphp
                <span class="{{ $bal >= 0 ? '' : 'text-danger' }}">
                    {{ number_format(abs($bal), 0, ',', '.') }}
                    {{ $bal < 0 ? '(Cr)' : '(Dr)' }}
                </span>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="7" class="text-center text-muted">Tidak ada data buku besar.</td>
        </tr>
        @endforelse
    </tbody>
    @if($summaryData->count() > 0)
    <tfoot>
        <tr class="total-row">
            <td colspan="4" class="text-right">GRAND TOTAL</td>
            <td class="text-right">{{ number_format($grandTotalDebit, 0, ',', '.') }}</td>
            <td class="text-right">{{ number_format($grandTotalCredit, 0, ',', '.') }}</td>
            <td class="text-right">
                @php $grandBal = $grandTotalDebit - $grandTotalCredit; @endphp
                {{ number_format(abs($grandBal), 0, ',', '.') }}
                {{ $grandBal < 0 ? '(Cr)' : '(Dr)' }}
            </td>
        </tr>
    </tfoot>
    @endif
</table>

{{-- Summary per Type --}}
@if($summaryData->count() > 0)
<div style="margin-top: 15px;">
    <div class="section-title">Ringkasan Per Tipe Akun</div>
    <table class="report-table" style="width: 60%;">
        <thead>
            <tr>
                <th width="30%">Tipe Akun</th>
                <th width="15%" class="text-center">Akun</th>
                <th width="25%" class="text-right">Total Debit</th>
                <th width="25%" class="text-right">Total Kredit</th>
            </tr>
        </thead>
        <tbody>
            @foreach(['aktiva', 'pasiva', 'modal', 'pendapatan', 'beban'] as $type)
                @php
                    $typed = $summaryData->where('coa_type', $type);
                @endphp
                @if($typed->count() > 0)
                <tr>
                    <td>
                        <span class="badge badge-{{ ['aktiva' => 'primary', 'pasiva' => 'warning', 'modal' => 'info', 'pendapatan' => 'success', 'beban' => 'danger'][$type] }}">
                            {{ $typeLabels[$type] }}
                        </span>
                    </td>
                    <td class="text-center">{{ $typed->count() }}</td>
                    <td class="text-right">{{ number_format($typed->sum('total_debit'), 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($typed->sum('total_credit'), 0, ',', '.') }}</td>
                </tr>
                @endif
            @endforeach
        </tbody>
    </table>
</div>
@endif
@endsection
