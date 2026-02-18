# KDS Tokoku — Roadmap & Ringkasan Sistem

> Dokumen ini mencatat progres pengembangan, arsitektur modul yang telah dibangun, dan rencana ke depan.
> Terakhir diperbarui: **18 Februari 2026**

---

## Statistik Proyek

| Metrik | Nilai |
|--------|-------|
| **Total Tests** | 1.191 tests, 2.766 assertions |
| **Framework** | Laravel 11 + Livewire v3 |
| **PHP** | 8.2.22 |
| **Database** | MySQL |
| **Pattern** | CRUD Management Pattern (lihat `CRUD-Management-Pattern.md`) |

---

## Phase 1 — Pondasi Akuntansi ✅

Modul dasar akuntansi yang menjadi tulang punggung seluruh sistem keuangan.

### 1.1 Chart of Accounts (COA)
- Master data akun keuangan (Aset, Liabilitas, Ekuitas, Pendapatan, Beban)
- Struktur hierarki akun dengan kode dan level
- Service: `COAService`

### 1.2 Jurnal & Buku Besar
- Input jurnal umum (debit/credit balancing)
- Buku besar per akun
- Service: `JournalService`

### 1.3 Neraca Saldo (Trial Balance)
- Neraca saldo reguler
- Jurnal penyesuaian
- Neraca saldo setelah penyesuaian (Adjusted Trial Balance)

### 1.4 Laporan Keuangan
- Laba Rugi (Income Statement)
- Neraca Keuangan Final (Balance Sheet)
- Export PDF

### 1.5 Perpajakan & Closing
- Koreksi fiskal (positif & negatif)
- Kompensasi kerugian
- Perhitungan PPh Badan
- Wizard penutupan buku (closing)

### 1.6 Periode Akuntansi
- Periode pembukuan per tahun
- Lock period setelah closing

---

## Phase 2 — Multi-Unit & Master Data ✅

Fondasi untuk mendukung usaha dengan banyak cabang/unit.

### 2.1 Unit Usaha (Business Unit)
- CRUD unit usaha (cabang/toko)
- Semua data ter-scope per unit usaha
- `BusinessUnitService` — auto-filter untuk non-superadmin

### 2.2 User & Role Management
- CRUD User, Role, Permission
- Spatie Permission integration
- Superadmin bisa akses semua unit

### 2.3 Jabatan (Position)
- Master data jabatan karyawan
- Linked ke unit usaha

---

## Phase 3 — Product & Inventory Management ✅

### 3.1 Kategori Stok
- Master kategori: Barang, Jasa, Saldo
- Tipe kategori menentukan perilaku produk

### 3.2 Grup Kategori
- Sub-kategorisasi di bawah kategori stok
- Hierarki: Kategori → Grup → Stok

### 3.3 Satuan (Unit of Measure)
- Master satuan: pcs, kg, liter, box, dll
- Seeder data default

### 3.4 Stok
- Master data produk/barang
- Tracking stok: `current_stock`, `min_stock`
- Low stock detection
- Barcode support

---

## Phase 4 — Business Partners ✅

### 4.1 Karyawan (Employee)
- Data pribadi, jabatan, status kerja
- Linked ke unit usaha & jabatan

### 4.2 Pelanggan (Customer)
- Data customer, kontak, alamat

### 4.3 Vendor
- Data supplier/penyedia barang & jasa

### 4.4 Partner
- Mitra usaha / pihak ketiga

---

## Phase 5 — Asset Management ✅

### 5.1 Kategori Aset
- Jenis aset: kendaraan, peralatan, properti, dll
- Metode penyusutan per kategori

### 5.2 Daftar Aset
- Master data aset perusahaan
- Harga perolehan, umur ekonomis, nilai sisa

### 5.3 Penyusutan (Depreciation)
- Perhitungan penyusutan otomatis (garis lurus)
- Tracking akumulasi penyusutan

### 5.4 Mutasi (Transfer)
- Perpindahan aset antar unit usaha

### 5.5 Perbaikan (Repair)
- Catatan perbaikan aset
- Biaya perbaikan

### 5.6 Disposal
- Pelepasan/penghapusan aset
- Tracking keuntungan/kerugian pelepasan

### 5.7 Laporan Aset
- Register aset
- Nilai buku
- Penyusutan per periode
- Riwayat aset

---

## Phase 6 — Financial Management ✅

### 6.1 Hutang Usaha (Payable)
- Tracking hutang ke vendor
- Pembayaran hutang (parsial/lunas)
- Aging report

### 6.2 Piutang Usaha (Receivable)
- Tracking piutang dari customer
- Penerimaan pembayaran (parsial/lunas)
- Aging report

### 6.3 Laporan AP/AR
- Aging Report (umur hutang/piutang)
- Outstanding Report
- Riwayat Pembayaran

### 6.4 Pinjaman Karyawan (Employee Loan)
- Input pinjaman karyawan
- Cicilan/angsuran
- Integrasi dengan payroll (potongan gaji)

---

## Phase 7 — Payroll (Penggajian) ✅

### 7.1 Komponen Gaji
- Komponen tunjangan (penambah)
- Komponen potongan (pengurang)
- Tipe: tetap, persentase

### 7.2 Setting Payroll
- Template gaji per karyawan
- Mapping komponen ke karyawan

### 7.3 Penggajian (Payroll)
- Buat payroll per periode per unit usaha
- Perhitungan otomatis: gaji pokok + tunjangan - potongan
- Potongan pinjaman otomatis dari employee loan
- Status: draft, approved, paid

### 7.4 Laporan Payroll
- Rekap payroll per periode
- Laporan per karyawan
- Laporan BPJS

---

## Phase 8 — Saldo Management ✅

> Modul untuk mengelola saldo digital: pulsa, token listrik, paket data, dll.

### 8.1 Penyedia Saldo (Provider)
- Master data penyedia: Buku Warung, Dana, Shopee Pay, dll
- Tipe: e-wallet, bank, lainnya
- Balance tracking per provider

### 8.2 Produk Saldo
- Daftar produk yang dijual: Pulsa 50K, Token 100K, dll
- Harga modal (buy_price) & harga jual (sell_price)
- Profit margin otomatis
- Optional linked ke provider tertentu

### 8.3 Top Up Saldo
- Catat pembelian saldo ke provider
- Metode: transfer, tunai, e-wallet, lainnya
- Balance provider otomatis bertambah (amount - fee)

### 8.4 Transaksi Saldo
- Catat penjualan saldo ke customer
- Auto-fill harga dari produk
- Profit otomatis dihitung (sell_price - buy_price)
- Balance provider otomatis berkurang (buy_price)

### Catatan Arsitektur Saldo:
- **Top Up & Transaksi di menu Saldo** = manual entry / quick action / koreksi
- **Nanti**: Top up utama lewat modul **Purchase**, penjualan utama lewat modul **Sales/POS**
- Modul Saldo tetap jadi **single source of truth** untuk balance tracking

---

## Phase 9 — Bank Management 🔜 (NEXT)

> Modul untuk mengelola rekening bank, kas, dan perpindahan dana.

### 9.1 Master Bank (`banks`)
- Daftar bank: BNI, BRI, BCA, Mandiri, dll
- CRUD sederhana

### 9.2 Rekening Bank (`bank_accounts`)
- Rekening milik usaha + balance tracking
- Nomor rekening, nama pemilik
- `initial_balance`, `current_balance` (auto-update)
- Per unit usaha

### 9.3 Kas Usaha (`cash_accounts`)
- Saldo kas per unit usaha
- **Auto-create default** saat unit usaha baru dibuat
- `current_balance` ter-update otomatis saat transfer

### 9.4 Fee Matrix (`bank_fee_matrix`)
- Biaya admin antar-bank (BNI→BRI = 2.500, sesama BNI = 0, dll)
- Auto-fill di form transfer, tapi **bisa di-override**
- Fleksibel untuk berbagai metode (BI-Fast, RTGS, dll)

### 9.5 Transfer Dana (`fund_transfers`)
- Perpindahan dana: Kas ↔ Bank, Bank ↔ Bank
- Source & destination polymorphic (cash / bank_account)
- Admin fee tracking — dibebankan ke pengirim
- Balance otomatis berubah di kedua sisi

### Contoh Alur Transfer:
```
Kas → BNI:   amount=5.000.000, fee=0      → Kas -5jt, BNI +5jt
BNI → BRI:   amount=1.000.000, fee=2.500  → BNI -1.002.500, BRI +1jt
BCA → Kas:   amount=500.000,   fee=0      → BCA -500rb, Kas +500rb
```

### Menu Sidebar (3 item):
- **Daftar Bank** — CRUD master bank + fee matrix
- **Rekening & Kas** — CRUD bank_accounts + lihat cash_accounts
- **Transfer Dana** — Catat perpindahan, balance otomatis berubah

---

## Phase 10 — Purchase (Pembelian) 📋 Planned

> Modul pengadaan barang, saldo, dan jasa dari vendor.

### Rencana:
- **Pembelian Langsung** — beli tanpa PO
- **Purchase Order** — order ke vendor
- **Penerimaan Barang** — terima barang dari vendor
- **Purchase Invoice** — tagihan dari vendor
- **Purchase Payment** — pembayaran ke vendor

### Integrasi:
- Purchase saldo → otomatis create `SaldoTopup` → balance provider naik
- Purchase barang → stok bertambah
- Purchase payment → bank/kas balance berkurang
- Hutang usaha otomatis tercatat

### Stock & Saldo Opname (dibangun bersamaan dengan Purchase):

**Stock Opname (Barang):**
- `stock_opnames` — Header: tanggal, unit usaha, status (draft/approved), penanggung jawab
- `stock_opname_details` — Per item: stock_id, `system_qty`, `actual_qty`, `difference`, `notes`
- Setelah approved → `stocks.current_stock` di-update ke `actual_qty`
- Selisih dicatat sebagai jurnal penyesuaian

**Saldo Opname:**
- `saldo_opnames` — Header: tanggal, unit usaha, status (draft/approved)
- `saldo_opname_details` — Per provider: saldo_provider_id, `system_balance`, `actual_balance`, `difference`, `notes`
- Setelah approved → `saldo_providers.current_balance` di-update
- Selisih dicatat sebagai penyesuaian

> **Alasan digabung di Phase 10**: Opname paling berguna saat sudah ada alur pengadaan (purchase) yang mengubah stok/saldo secara nyata, sehingga bisa langsung diverifikasi.

---

## Phase 11 — Sales & POS (Penjualan) 📋 Planned

> Modul penjualan barang, saldo, dan jasa ke customer.

### Rencana:
- **POS (Point of Sales)** — kasir frontend
- **Penjualan Langsung** — tanpa SO
- **Sales Order** — order dari customer
- **Pengiriman Barang** — kirim barang ke customer
- **Sales Invoice** — tagihan ke customer
- **Sales Payment** — penerimaan pembayaran

### Integrasi:
- Penjualan saldo → otomatis create `SaldoTransaction` → balance provider turun
- Penjualan barang → stok berkurang
- Penjualan jasa (transfer bank, pembuatan aplikasi, dll) → tanpa stok
- Sales payment → bank/kas balance bertambah
- Piutang usaha otomatis tercatat

### Fitur Harga Fleksibel (direncanakan):
- **`saldo_price_adjustments`** — tracking perubahan harga produk saldo
- Tipe: `permanent` / `temporary_discount` / `vendor_promo`
- Kasir bisa klik "Ubah Harga" → sistem tanya: permanen atau sementara?
- Diskon sementara punya `end_date`, auto-expire
- Transaksi selalu simpan harga aktual (sudah ada di `saldo_transactions`)

### Jenis Penjualan Jasa:
| Tipe | Contoh | Stok? | Harga |
|------|--------|-------|-------|
| Jasa transfer bank | Transfer ke BNI/BRI | Tidak | Fleksibel (fee bank + margin) |
| Jasa digital (saldo) | Pulsa, token listrik | Ya (balance) | From product master |
| Jasa profesional | Pembuatan aplikasi | Tidak | Custom per project |

---

## Phase 12+ — Rencana Jangka Panjang 📋 Planned

### 12.1 Bank Reconciliation
- Rekonsiliasi saldo bank vs catatan internal
- Import mutasi bank (CSV/Excel)
- Matching otomatis & manual

### 12.2 Dashboard
- Ringkasan per unit usaha
- Grafik pendapatan, pengeluaran, laba
- Alert: stok rendah, hutang jatuh tempo, aset perlu maintenance

### 12.3 Project/Job Order Management
- Untuk jasa kompleks (pembuatan aplikasi, proyek)
- Tracking progress, milestone, billing per tahap

### 12.4 Laporan Pajak Lanjutan
- SPT Tahunan
- Faktur Pajak
- E-Faktur integration

---

## Arsitektur & Konvensi

### Stack Teknologi
- **Backend**: Laravel 11, PHP 8.2
- **Frontend**: Livewire v3, Bootstrap, Blade
- **Database**: MySQL
- **Testing**: PHPUnit (Feature tests)
- **Package**: Spatie Permission, DomPDF, Blameable trait

### Pattern yang Digunakan
- **CRUD Management Pattern** — Controller → Page View → Livewire List/Form → Blade
- **BusinessUnitService** — Multi-tenant scoping, auto-filter per user
- **Service Layer** — Business logic di Service class (DB::transaction)
- **Model::withoutEvents()** — Digunakan di tests untuk bypass Blameable
- **Bahasa**: UI dalam Bahasa Indonesia, code/variable dalam English

### Struktur File per Modul
```
app/
├── Http/Controllers/{Module}Controller.php
├── Livewire/{Module}/
│   ├── {Module}List.php
│   └── {Module}Form.php
├── Models/{Model}.php
└── Services/{Module}Service.php

resources/views/
├── pages/{module}/{page}.blade.php
└── livewire/{module}/
    ├── {module}-list.blade.php
    └── {module}-form.blade.php

database/migrations/
└── {date}_create_{module}_tables.php

tests/Feature/
└── {Module}Test.php
```

### Menu Sidebar (Struktur Aktif)
```
Main
└── Dashboard (placeholder)

Master Data
├── Perusahaan & Sistem
│   ├── Unit Usaha ✅
│   ├── User ✅
│   ├── Role ✅
│   ├── Permission ✅
│   └── Jabatan ✅
└── Config Akuntansi
    ├── Chart of Accounts ✅
    └── Periode (placeholder)

Product Management
└── Stok
    ├── Kategori Stok ✅
    ├── Grup Kategori ✅
    ├── Satuan ✅
    └── Stok ✅

Inventory & Saldo
└── Saldo
    ├── Penyedia Saldo ✅
    ├── Produk Saldo ✅
    ├── Top Up Saldo ✅
    └── Transaksi Saldo ✅

Business Partners
└── Kartu Nama
    ├── Karyawan ✅
    ├── Pelanggan ✅
    ├── Vendor ✅
    └── Partner ✅

Asset Management
├── Manajemen Aset
│   ├── Kategori Aset ✅
│   ├── Daftar Aset ✅
│   ├── Penyusutan ✅
│   ├── Mutasi ✅
│   ├── Perbaikan ✅
│   └── Disposal ✅
└── Laporan Aset
    ├── Daftar Aset ✅
    ├── Nilai Buku ✅
    ├── Penyusutan per Periode ✅
    └── Riwayat Aset ✅

Transaction
├── Purchase (placeholder — 5 submenu)
└── Sales (placeholder — 6 submenu)

Financial Management
├── Hutang / Piutang
│   ├── Hutang Usaha ✅
│   └── Piutang Usaha ✅
├── Laporan AP/AR
│   ├── Aging Report ✅
│   ├── Outstanding Report ✅
│   └── Riwayat Pembayaran ✅
└── Pinjaman
    └── Pinjaman Karyawan ✅

Payroll
├── Penggajian
│   ├── Komponen Gaji ✅
│   ├── Setting Payroll ✅
│   └── Payroll ✅
└── Laporan Payroll
    ├── Rekap Payroll ✅
    ├── Laporan per Karyawan ✅
    └── Laporan BPJS ✅

Akuntansi
├── Jurnal ✅
├── Buku Besar ✅
├── Neraca Saldo ✅
├── Laba Rugi ✅
├── Jurnal Penyesuaian ✅
├── Neraca Penyesuaian ✅
└── Perpajakan & Closing ✅

Laporan Keuangan
└── Neraca Keuangan Final ✅

Banking & Reconciliation (placeholder — 4 submenu)
└── Bank
    ├── Rekening Bank 🔜
    ├── Transaksi Bank 🔜
    ├── Template Biaya 🔜
    └── Rekonsiliasi 🔜
```

---

## Keputusan Desain Penting

### 1. Saldo: Master + Quick Action, Operasi Utama di Purchase/Sales
- Menu Saldo menyediakan manual entry untuk fleksibilitas
- Alur utama nanti: Purchase → SaldoTopup, Sales → SaldoTransaction
- Modul Saldo = **single source of truth** untuk balance

### 2. Harga Produk Saldo: Fleksibel
- `saldo_products` menyimpan harga standar (auto-fill)
- `saldo_transactions` menyimpan harga aktual per transaksi (sudah fleksibel)
- Nanti ditambah `saldo_price_adjustments` untuk tracking: permanen vs diskon sementara vs promo vendor

### 3. Bank Management: Balance Tracking Mandiri
- Kas & Bank punya `current_balance` masing-masing
- Setiap transfer/transaksi otomatis update balance
- Fee matrix sebagai saran, bisa di-override

### 4. Jasa = Bagian dari Sales
- Jasa transfer bank, jasa digital (saldo), jasa profesional — semua masuk Sales
- Perbedaannya: jasa tidak punya stok fisik
- `stock_categories.type = 'jasa'` sudah tersedia

### 5. Cash Account Default
- Setiap unit usaha otomatis punya 1 cash account
- Balance ter-update saat ada transfer kas ↔ bank
