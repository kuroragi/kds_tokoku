@extends('pdf.layout')

@section('content')
<div class="summary-box info">
    <strong>Akun:</strong> {{ $coa->code }} — {{ $coa->name }}
    &nbsp;|&nbsp;
    <strong>Tipe:</strong> {{ ucfirst($coa->type) }}
    &nbsp;|&nbsp;
    <strong>Total Transaksi:</strong> {{ $entries->count() }}
</div>

<table class="report-table">
    <thead>
        <tr>
            <th width="12%">Tanggal</th>
            <th width="12%">No. Jurnal</th>
            <th width="12%">Referensi</th>
            <th width="24%">Keterangan</th>
            <th width="13%" class="text-right">Debit</th>
            <th width="13%" class="text-right">Kredit</th>
            <th width="14%" class="text-right">Saldo</th>
        </tr>
    </thead>
    <tbody>
        @forelse($entries as $entry)
        <tr>
            <td>{{ \Carbon\Carbon::parse($entry->journal_date)->format('d/m/Y') }}</td>
            <td class="fw-bold">{{ $entry->journal_no }}</td>
            <td>{{ $entry->reference ?? '-' }}</td>
            <td>{{ $entry->description ?: $entry->journal_description }}</td>
            <td class="text-right">
                @if($entry->debit > 0)
                    {{ number_format($entry->debit, 0, ',', '.') }}
                @else
                    <span class="text-muted">-</span>
                @endif
            </td>
            <td class="text-right">
                @if($entry->credit > 0)
                    {{ number_format($entry->credit, 0, ',', '.') }}
                @else
                    <span class="text-muted">-</span>
                @endif
            </td>
            <td class="text-right fw-bold">
                <span class="{{ $entry->running_balance >= 0 ? '' : 'text-danger' }}">
                    {{ number_format(abs($entry->running_balance), 0, ',', '.') }}
                    {{ $entry->running_balance < 0 ? '(Cr)' : '(Dr)' }}
                </span>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="7" class="text-center text-muted">Tidak ada transaksi untuk akun ini.</td>
        </tr>
        @endforelse
    </tbody>
    @if($entries->count() > 0)
    <tfoot>
        <tr class="total-row">
            <td colspan="4" class="text-right">TOTAL</td>
            <td class="text-right">{{ number_format($totalDebit, 0, ',', '.') }}</td>
            <td class="text-right">{{ number_format($totalCredit, 0, ',', '.') }}</td>
            <td class="text-right">
                {{ number_format(abs($finalBalance), 0, ',', '.') }}
                {{ $finalBalance < 0 ? '(Cr)' : '(Dr)' }}
            </td>
        </tr>
    </tfoot>
    @endif
</table>
@endsection
