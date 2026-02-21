# KDS Tokoku — Roadmap & Ringkasan Sistem

> Dokumen ini mencatat progres pengembangan, arsitektur modul yang telah dibangun, dan rencana ke depan.
> Terakhir diperbarui: **21 Februari 2026**

---

## Statistik Proyek

| Metrik | Nilai |
|--------|-------|
| **Total Migrations** | 32 |
| **Total Models** | 72 |
| **Total Services** | 21 |
| **Total Livewire Components** | 115 |
| **Total Controllers** | 15 |
| **Total Routes** | ~63 |
| **Framework** | Laravel 12 + Livewire v3 |
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
- Export PDF (7 endpoint: TB, BS, IS, ATB, GL, GL Detail, Final BS)

### 1.5 Perpajakan & Closing
- Koreksi fiskal (positif & negatif)
- Kompensasi kerugian
- Perhitungan PPh Badan
- Wizard penutupan buku (closing) — 5 step

### 1.6 Periode Akuntansi
- Model + migration tersedia
- ⚠️ **Belum ada CRUD UI** — dikelola via seeder/tinker

---

## Phase 2 — Multi-Unit & Master Data ✅

### 2.1 Unit Usaha (Business Unit)
- CRUD unit usaha (cabang/toko)
- COA Mapping per unit
- Semua data ter-scope per unit usaha
- `BusinessUnitService` — auto-filter non-superadmin
- Observer auto-create cash account

### 2.2 User & Role Management
- CRUD User, Role, Permission
- Spatie Permission integration
- Superadmin bisa akses semua unit

### 2.3 Jabatan (Position)
- Master data jabatan karyawan
- Template komponen gaji per jabatan

---

## Phase 3 — Product & Inventory Management ✅

### 3.1 Kategori Stok — `stock_categories`
### 3.2 Grup Kategori — `category_groups`
### 3.3 Satuan (UoM) — `unit_of_measures`
### 3.4 Stok — `stocks` (current_stock, min_stock, barcode)
### 3.5 Warehouse Monitor — dashboard monitoring stok real-time

---

## Phase 4 — Business Partners ✅

### 4.1 Karyawan — `employees` (data pribadi, jabatan, status kerja)
### 4.2 Pelanggan — `customers` (kontak, alamat, NPWP)
### 4.3 Vendor — `vendors` (supplier, NPWP)
### 4.4 Partner — `partners` (mitra usaha)

---

## Phase 5 — Asset Management ✅

### 5.1 Kategori Aset — metode penyusutan per kategori
### 5.2 Daftar Aset — harga perolehan, umur ekonomis, nilai sisa
### 5.3 Penyusutan — perhitungan otomatis (garis lurus)
### 5.4 Mutasi — perpindahan aset antar unit usaha
### 5.5 Perbaikan — catatan & biaya perbaikan
### 5.6 Disposal — pelepasan + tracking keuntungan/kerugian
### 5.7 Laporan Aset — 4 laporan (Register, Nilai Buku, Penyusutan, Riwayat)

---

## Phase 6 — Financial Management ✅

### 6.1 Hutang Usaha (AP) — tracking + pembayaran parsial/lunas + aging
### 6.2 Piutang Usaha (AR) — tracking + penerimaan + aging
### 6.3 Laporan AP/AR — Aging, Outstanding, Riwayat Pembayaran
### 6.4 Pinjaman Karyawan — pinjaman + cicilan + integrasi payroll

---

## Phase 7 — Payroll (Penggajian) ✅

### 7.1 Komponen Gaji — tunjangan & potongan (tetap/persentase)
### 7.2 Setting Payroll — template gaji per karyawan
### 7.3 Penggajian — kalkulasi otomatis + PPh21 TER + BPJS
### 7.4 Laporan Payroll — Rekap, Per Karyawan, BPJS

---

## Phase 8 — Saldo Management ✅

### 8.1 Penyedia Saldo — e-wallet/bank provider + balance tracking
### 8.2 Produk Saldo — harga modal & jual + profit margin
### 8.3 Top Up Saldo — pembelian saldo ke provider
### 8.4 Transaksi Saldo — penjualan saldo ke customer

---

## Phase 9 — Bank Management ✅

### 9.1 Master Bank — CRUD bank (BNI, BRI, BCA, dll)
### 9.2 Rekening Bank — balance tracking per unit usaha
### 9.3 Kas Usaha — auto-create per unit, balance auto-update
### 9.4 Fee Matrix — biaya antar bank (auto-fill, bisa override)
### 9.5 Transfer Dana — Kas↔Bank, Bank↔Bank, balance otomatis

---

## Phase 10 — Purchase & Opname ✅

### 10.1 Purchase Order — workflow draft→confirmed→received
### 10.2 Pembelian — direct + dari PO + penerimaan parsial
### 10.3 Pembayaran — Cash/Credit/Partial/Down Payment + auto AP
### 10.4 Stock Opname — verifikasi stok + auto-adjust + jurnal
### 10.5 Saldo Opname — verifikasi balance provider + auto-adjust

---

## Phase 11 — Sales (Penjualan) ✅

### 11.1 Penjualan — CRUD sale + barang/saldo/jasa
### 11.2 Pembayaran — Cash/Credit/Partial + auto AR
### 11.3 Integrasi — stok berkurang, saldo berkurang, jurnal otomatis

---

## Phase 12 — Advanced Features ✅

### 12.1 Bank Reconciliation
- Import mutasi bank (CSV/Excel via Maatwebsite)
- Preset kolom BCA/BNI/BRI/Mandiri
- Auto-matching (3 strategi: reference, fund transfer, journal)
- Manual matching + adjustment

### 12.2 Dashboard
- Summary cards (penjualan, pembelian, laba kotor, customer baru)
- Grafik ApexCharts (Sales vs Purchases trend)
- Cash flow, hutang/piutang, bank balance
- Top produk, low stock alerts, transaksi terbaru
- Filter periode + unit usaha

### 12.3 Project / Job Order
- CRUD project (planning→active→completed)
- Tracking biaya (material/labor/overhead)
- Tracking pendapatan
- Budget vs actual + profit margin

### 12.4 Laporan Pajak Lanjutan
- Faktur Pajak (keluaran/masukan) — CRUD + status workflow
- SPT Masa PPN — ringkasan keluaran vs masukan, kurang/lebih bayar
- SPT Tahunan — peredaran usaha, HPP, PPN bulanan, PPh Badan
- Generate otomatis dari transaksi penjualan & pembelian

### 12.5 Saldo Awal (Opening Balance)
- Input saldo awal per COA per unit usaha per periode
- Posting ke jurnal otomatis
- Unpost / delete support

---

## Backend Audit Summary (21 Feb 2026)

### Completeness: 58/59 modul ✅

| Group | Modules | Status |
|-------|---------|--------|
| Akuntansi | COA, Jurnal, Buku Besar, Neraca Saldo, Laba Rugi, Adj. Journal, Adj. TB, Final BS, PDF Reports | ✅ All Complete |
| Perpajakan | Tax Closing Wizard (5 step), Faktur Pajak, SPT Masa/Tahunan | ✅ All Complete |
| Master Data | Business Unit, User/Role/Permission, Position, Employee, Customer, Vendor, Partner | ✅ All Complete |
| Inventory | Stock Category, Category Group, UoM, Stock, Warehouse Monitor | ✅ All Complete |
| Saldo | Provider, Product, TopUp, Transaction, Opname | ✅ All Complete |
| Asset | Category, List, Depreciation, Transfer, Repair, Disposal, 4 Reports | ✅ All Complete |
| Keuangan | AP, AR, 3 Reports, Employee Loan | ✅ All Complete |
| Payroll | Komponen, Setting, Payroll, 3 Reports | ✅ All Complete |
| Bank | Master, Account, Kas, Transfer, Mutation, Reconciliation | ✅ All Complete |
| Purchase | PO, Purchase, Payment, Stock Opname, Saldo Opname | ✅ All Complete |
| Sales | Sale, Payment | ✅ All Complete |
| Advanced | Dashboard, Project, Opening Balance | ✅ All Complete |
| Periode | Model only | ⚠️ No CRUD UI |

**Backend ERP READY — siap masuk tahap frontend SaaS.**

---

## Phase 13 — SaaS Frontend & Pricing 📋 NEXT

> Transformasi dari internal ERP menjadi produk SaaS multi-tenant untuk UMKM Indonesia.

### 13.1 Landing Page & Marketing Site
- Hero section dengan value proposition untuk UMKM
- Feature showcase (modul-modul ERP)
- Pricing table interaktif (4 paket)
- Testimoni, FAQ, CTA
- Responsive design (mobile-first)

### 13.2 Pricing & Paket Berlangganan

| Fitur | Trial | Basic | Medium | Premium |
|-------|-------|-------|--------|---------|
| **Harga/bulan** | Gratis (14 hari) | Rp 99.000 | Rp 249.000 | Rp 499.000 |
| **Unit Usaha** | 1 | 1 | 3 | Unlimited |
| **User** | 1 | 3 | 10 | Unlimited |
| **COA & Jurnal** | ✅ | ✅ | ✅ | ✅ |
| **Neraca & Laba Rugi** | ✅ | ✅ | ✅ | ✅ |
| **Master Data (Stok/Customer/Vendor)** | ✅ | ✅ | ✅ | ✅ |
| **Pembelian (Purchase)** | ❌ | ✅ | ✅ | ✅ |
| **Penjualan (Sales)** | ❌ | ✅ | ✅ | ✅ |
| **Hutang/Piutang (AP/AR)** | ❌ | ✅ | ✅ | ✅ |
| **Bank & Transfer** | ❌ | ✅ | ✅ | ✅ |
| **Saldo Management** | ❌ | ✅ | ✅ | ✅ |
| **PDF Reports** | ❌ | ✅ | ✅ | ✅ |
| **Asset Management** | ❌ | ❌ | ✅ | ✅ |
| **Payroll** | ❌ | ❌ | ✅ | ✅ |
| **Employee Loan** | ❌ | ❌ | ✅ | ✅ |
| **Perpajakan (SPT/Faktur)** | ❌ | ❌ | ✅ | ✅ |
| **Opening Balance** | ❌ | ❌ | ✅ | ✅ |
| **Stock Opname** | ❌ | ❌ | ✅ | ✅ |
| **Export Excel** | ❌ | ❌ | ✅ | ✅ |
| **Multi-Role & Permission** | ❌ | ❌ | ✅ | ✅ |
| **Bank Reconciliation** | ❌ | ❌ | ❌ | ✅ |
| **Project / Job Order** | ❌ | ❌ | ❌ | ✅ |
| **Dashboard Advanced** | Basic | Basic | Full | Full |
| **Support** | Community | Email | Priority | Dedicated |

> **Catatan**: Superadmin access = internal team only, tidak tersedia di paket manapun.

### 13.3 Tenant & Subscription System
- Tabel `tenants` — profil perusahaan, plan, status
- Tabel `subscriptions` — plan, durasi, payment status
- Tabel `plans` & `plan_features` — konfigurasi fitur per paket
- Middleware `CheckSubscription` — gating fitur per plan
- Grace period (3 hari) setelah expired
- Trial auto-expire 14 hari

### 13.4 Registration & Onboarding
- Register + email verification
- Wizard onboarding (profil usaha → pilih paket → setup awal)
- Auto-create business unit + cash account + default COA
- Sample data option (untuk trial)

### 13.5 UI/UX Redesign
- Modern sidebar + topbar
- Consistent design system
- Loading states, empty states, error states
- Mobile-responsive
- Dark mode (optional)

### 13.6 Payment Integration
- Midtrans / Xendit
- Invoice otomatis
- Auto-activate/deactivate
- Notifikasi jatuh tempo

---

## Phase 14 — Polish & Production 📋 Future

### 14.1 Audit Log / Activity Log
### 14.2 Notification System (email + in-app)
### 14.3 Data Import/Export (bulk)
### 14.4 API untuk integrasi pihak ketiga
### 14.5 Backup & Restore per tenant
### 14.6 Performance optimization (caching, queue)
### 14.7 POS (Point of Sales) — kasir frontend

---

## Arsitektur & Konvensi

### Stack Teknologi
- **Backend**: Laravel 12, PHP 8.2
- **Frontend**: Livewire v3, Bootstrap 5, Blade, ApexCharts
- **Database**: MySQL
- **Package**: Spatie Permission, DomPDF, Maatwebsite Excel, Blameable trait

### Pattern yang Digunakan
- **CRUD Management Pattern** — Controller → Page View → Livewire List/Form → Blade
- **BusinessUnitService** — Multi-tenant scoping, auto-filter per user
- **Service Layer** — Business logic di Service class (DB::transaction)
- **Computed Properties** — Livewire 3: `$this->propertyName` di blade
- **Bahasa**: UI dalam Bahasa Indonesia, code/variable dalam English

### Inventory

| Komponen | Jumlah |
|----------|--------|
| Migrations | 32 |
| Models | 72 |
| Services | 21 |
| Livewire Components | 115 |
| Blade Views | 120+ |
| Controllers | 15 |
| Routes | ~63 |
| Database Tables | ~55 |
