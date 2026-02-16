@extends('pdf.layout')

@section('content')
<table class="report-table">
    <thead>
        <tr>
            <th width="12%">Kode</th>
            <th width="30%">Nama Akun</th>
            <th width="13%">Tipe</th>
            <th width="15%" class="text-right">Total Debit</th>
            <th width="15%" class="text-right">Total Kredit</th>
            <th width="15%" class="text-right">Saldo</th>
        </tr>
    </thead>
    <tbody>
        @forelse($data['accounts'] as $account)
        <tr>
            <td class="text-primary fw-bold">{{ $account->coa_code }}</td>
            <td>{{ $account->coa_name }}</td>
            <td>
                <span class="badge badge-{{ ['aktiva' => 'primary', 'pasiva' => 'warning', 'modal' => 'info', 'pendapatan' => 'success', 'beban' => 'danger'][$account->coa_type] ?? 'primary' }}">
                    {{ ucfirst($account->coa_type) }}
                </span>
            </td>
            <td class="text-right">
                @if($account->saldo_debit > 0)
                    {{ number_format($account->saldo_debit, 0, ',', '.') }}
                @else
                    <span class="text-muted">-</span>
                @endif
            </td>
            <td class="text-right">
                @if($account->saldo_credit > 0)
                    {{ number_format($account->saldo_credit, 0, ',', '.') }}
                @else
                    <span class="text-muted">-</span>
                @endif
            </td>
            <td class="text-right fw-bold">
                @php $bal = $account->total_debit - $account->total_credit; @endphp
                {{ number_format(abs($bal), 0, ',', '.') }}
                {{ $bal < 0 ? '(Cr)' : '(Dr)' }}
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="6" class="text-center text-muted">Tidak ada data neraca saldo.</td>
        </tr>
        @endforelse
    </tbody>
    @if($data['accounts']->count() > 0)
    <tfoot>
        <tr class="total-row">
            <td colspan="3" class="text-right">TOTAL</td>
            <td class="text-right">{{ number_format($data['total_debit'], 0, ',', '.') }}</td>
            <td class="text-right">{{ number_format($data['total_credit'], 0, ',', '.') }}</td>
            <td class="text-right">
                @if(abs($data['total_debit'] - $data['total_credit']) < 0.01)
                    SEIMBANG
                @else
                    TIDAK SEIMBANG
                @endif
            </td>
        </tr>
    </tfoot>
    @endif
</table>

@if($data['accounts']->count() > 0)
<div class="summary-box {{ abs($data['total_debit'] - $data['total_credit']) < 0.01 ? 'success' : 'danger' }}">
    <strong>Status:</strong>
    @if(abs($data['total_debit'] - $data['total_credit']) < 0.01)
        Neraca Saldo SEIMBANG — Total Debit dan Kredit sama yaitu {{ number_format($data['total_debit'], 0, ',', '.') }}
    @else
        Neraca Saldo TIDAK SEIMBANG — Selisih: {{ number_format(abs($data['total_debit'] - $data['total_credit']), 0, ',', '.') }}
    @endif
</div>
@endif
@endsection
