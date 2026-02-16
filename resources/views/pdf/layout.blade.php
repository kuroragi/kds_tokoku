<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $title ?? 'Laporan Keuangan' }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            color: #333;
            line-height: 1.4;
        }

        .header {
            text-align: center;
            border-bottom: 3px double #333;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .header h1 {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 2px;
        }

        .header h2 {
            font-size: 13px;
            font-weight: bold;
            color: #444;
            margin-bottom: 4px;
        }

        .header .filter-info {
            font-size: 10px;
            color: #666;
        }

        .print-info {
            font-size: 8px;
            color: #999;
            text-align: right;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        table.report-table th {
            background-color: #2c3e50;
            color: #fff;
            font-weight: bold;
            padding: 6px 8px;
            text-align: left;
            font-size: 9px;
            text-transform: uppercase;
        }

        table.report-table td {
            padding: 5px 8px;
            border-bottom: 1px solid #e0e0e0;
            font-size: 9px;
        }

        table.report-table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        table.report-table tfoot td {
            font-weight: bold;
            border-top: 2px solid #333;
            background-color: #ecf0f1;
            padding: 6px 8px;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .text-left {
            text-align: left;
        }

        .fw-bold {
            font-weight: bold;
        }

        .text-success {
            color: #27ae60;
        }

        .text-danger {
            color: #e74c3c;
        }

        .text-muted {
            color: #999;
        }

        .text-primary {
            color: #2c3e50;
        }

        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .badge-primary { background-color: #3498db; color: #fff; }
        .badge-warning { background-color: #f39c12; color: #fff; }
        .badge-info { background-color: #17a2b8; color: #fff; }
        .badge-success { background-color: #27ae60; color: #fff; }
        .badge-danger { background-color: #e74c3c; color: #fff; }

        .summary-box {
            border: 1px solid #ddd;
            padding: 8px 12px;
            margin-bottom: 10px;
            border-radius: 4px;
        }

        .summary-box.success {
            background-color: #d4edda;
            border-color: #c3e6cb;
        }

        .summary-box.danger {
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }

        .summary-box.info {
            background-color: #d1ecf1;
            border-color: #bee5eb;
        }

        .section-title {
            font-size: 11px;
            font-weight: bold;
            padding: 6px 8px;
            margin: 10px 0 5px;
            border-left: 4px solid #2c3e50;
            background-color: #f8f9fa;
        }

        .section-title.aktiva { border-color: #3498db; }
        .section-title.pasiva { border-color: #f39c12; }
        .section-title.modal { border-color: #17a2b8; }
        .section-title.pendapatan { border-color: #27ae60; }
        .section-title.beban { border-color: #e74c3c; }

        .two-column {
            width: 100%;
        }

        .two-column td {
            width: 50%;
            vertical-align: top;
            padding: 0 5px;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8px;
            color: #999;
            border-top: 1px solid #ddd;
            padding-top: 5px;
        }

        .page-break {
            page-break-after: always;
        }

        .total-row {
            background-color: #2c3e50 !important;
            color: #fff !important;
        }

        .total-row td {
            color: #fff !important;
            font-weight: bold;
            border-top: 2px solid #333;
        }

        .subtotal-row {
            background-color: #ecf0f1 !important;
        }

        .subtotal-row td {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>KDS Tokoku</h1>
        <h2>{{ $title }}</h2>
        <div class="filter-info">{{ $filterDescription }}</div>
    </div>

    <div class="print-info">
        Dicetak pada: {{ $printDate }}
    </div>

    @yield('content')

    <div class="footer">
        KDS Tokoku — Sistem Informasi Akuntansi | {{ $printDate }}
    </div>
</body>
</html>
