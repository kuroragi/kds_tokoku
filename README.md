# KDS Tokoku - Sistem Manajemen Toko

![Laravel](https://img.shields.io/badge/Laravel-11.x-red.svg)
![PHP](https://img.shields.io/badge/PHP-8.2+-blue.svg)
![License](https://img.shields.io/badge/License-MIT-green.svg)

KDS Tokoku adalah sistem manajemen toko yang dibangun menggunakan Laravel 11 dengan fitur accounting system yang lengkap. Aplikasi ini dirancang untuk membantu pengelolaan toko dengan sistem akuntansi yang terintegrasi.

## 📋 Fitur Utama

### 🔐 Authentication & Authorization

-   User management dengan role-based permissions
-   Menggunakan Spatie Permission package
-   Soft delete untuk data users
-   Blameable traits untuk audit trail

### 📊 Accounting System

-   **Chart of Accounts (COA)** - Bagan akun dengan hierarki
-   **Journal Master** - Master jurnal untuk grouping transaksi
-   **Journal Details** - Detail jurnal dengan sistem debit-credit
-   Sistem accounting 5 kategori: Asset, Liability, Equity, Revenue, Expense
-   Support untuk parent-child relationship pada COA

### 🛠 Technical Features

-   Built with Laravel 11
-   MySQL database support
-   Soft deletes on all main entities
-   Blameable trait for created_by, updated_by, deleted_by tracking
-   Model relationships dengan Eloquent ORM
-   Database seeding dengan data Chart of Accounts dalam Bahasa Indonesia

## 🚀 Instalasi

### Prasyarat

-   PHP 8.2 atau lebih tinggi
-   Composer
-   MySQL/MariaDB
-   Node.js & NPM

### Langkah Instalasi

1. **Clone Repository**

    ```bash
    git clone <repository-url>
    cd kds_tokoku
    ```

2. **Install Dependencies**

    ```bash
    composer install
    npm install
    ```

3. **Environment Setup**

    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

4. **Database Configuration**
   Edit file `.env` dan sesuaikan pengaturan database:

    ```env
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=kds_tokoku
    DB_USERNAME=root
    DB_PASSWORD=
    ```

5. **Database Migration & Seeding**

    ```bash
    php artisan migrate:fresh --seed
    ```

6. **Storage Link**

    ```bash
    php artisan storage:link
    ```

7. **Build Assets**

    ```bash
    npm run build
    ```

8. **Run Application**
    ```bash
    php artisan serve
    ```

## 📂 Struktur Database

### Users Table

-   User management dengan role permissions
-   Fields: name, username, email, password
-   Soft deletes & blameable

### COA (Chart of Accounts)

-   Bagan akun dengan hierarki
-   Fields: code, name, type, parent_id, level, description, is_active
-   Types: asset, liability, equity, revenue, expense

### Journal Master

-   Header jurnal transaksi
-   Fields: journal_no, journal_date, reference, description, total_debit, total_credit, status
-   Status: draft, posted, cancelled

### Journal Details

-   Detail jurnal dengan debit/credit entries
-   Fields: id_journal_master, id_coa, description, debit, credit, sequence
-   Relationship ke COA dan Journal Master

## 📊 Chart of Accounts (Bagan Akun)

Aplikasi sudah dilengkapi dengan Chart of Accounts dalam bahasa Indonesia:

### 🏦 ASET (1000)

-   **Aset Lancar (1100)**
    -   Kas di Tangan (1101)
    -   Kas di Bank (1102)
    -   Piutang Dagang (1201)
    -   Persediaan Barang (1301)

### 💳 KEWAJIBAN (2000)

-   **Kewajiban Lancar (2100)**
    -   Hutang Dagang (2101)
    -   Hutang Pajak (2201)

### 💰 MODAL (3000)

-   Modal Saham (3101)
-   Laba Ditahan (3201)

### 💵 PENDAPATAN (4000)

-   Pendapatan Penjualan (4101)
-   Pendapatan Jasa (4201)

### 💸 BEBAN (5000)

-   Beban Pokok Penjualan (5101)
-   Beban Operasional (5201)
-   Beban Administrasi (5301)

## 🏗 Arsitektur

### Models & Relationships

-   **User** → hasRoles, soft deletes, blameable
-   **COA** → self-referencing, hasMany journals
-   **JournalMaster** → hasMany journals
-   **Journal** → belongsTo journalMaster, belongsTo coa

### Traits Used

-   `SoftDeletes` - untuk soft delete functionality
-   `Blameable` - untuk audit trail (created_by, updated_by, deleted_by)
-   `HasRoles` - untuk role-based permissions

## 🛡 Packages Used

-   **spatie/laravel-permission** - Role & Permission management
-   **kuroragi/general-helper** - Blameable traits & Blueprint macros
-   **barryvdh/laravel-dompdf** - PDF generation

## 🤝 Contributing

Jika Anda ingin berkontribusi pada proyek ini:

1. Fork repository
2. Buat feature branch (`git checkout -b feature/amazing-feature`)
3. Commit perubahan (`git commit -m 'Add amazing feature'`)
4. Push ke branch (`git push origin feature/amazing-feature`)
5. Buka Pull Request

## 📄 License

Proyek ini dilisensikan di bawah [MIT License](https://opensource.org/licenses/MIT).
