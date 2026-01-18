# CRUD Management System Pattern

## Overview

This document describes the universal pattern for creating CRUD (Create, Read, Update, Delete) management systems in the Laravel application. This pattern can be used consistently across different modules like COA, Journal, and other similar features.

## Language Guidelines

**Bahasa yang Digunakan: Bahasa Indonesia**

-   Semua teks interface, pesan, label, dan dokumentasi menggunakan Bahasa Indonesia yang baik dan benar
-   Istilah teknis tetap dalam bahasa asli: "journal", "debit", "credit", "posting", dll.
-   Nama field dan variabel tetap dalam bahasa Inggris untuk konsistensi kode
-   Pesan error dan validasi dalam Bahasa Indonesia
-   Tombol dan menu dalam Bahasa Indonesia: "Simpan", "Batal", "Hapus", "Edit", dll.

Contoh konsistensi bahasa:

-   ✅ "Tambah Journal Entry" (bukan "Tambah Jurnal Entry")
-   ✅ "Edit Chart of Account" (bukan "Edit Bagan Akun")
-   ✅ "Total Debit" (bukan "Total Hutang")
-   ✅ "Simpan Data" (bukan "Save Data")
-   ✅ "Hapus Record" (bukan "Delete Record")

## Architecture Pattern

### 1. Controller Layer

```
app/Http/Controllers/{ModuleName}Controller.php
```

-   Simple controller with methods that return views
-   Each method corresponds to a page/feature
-   Example: `coa()`, `journal()`

### 2. View Structure

```
resources/views/pages/{module}/
├── {module}.blade.php (Main page)
└── guide-modal/
    └── {module}-guide-modal.blade.php
```

### 3. Livewire Components

```
app/Livewire/{ModuleName}/
├── {ModuleName}List.php (List component)
└── {ModuleName}Form.php (Form component)
```

### 4. Livewire Views

```
resources/views/livewire/{module}/
├── {module}-list.blade.php (List view)
└── {module}-form.blade.php (Form view)
```

## Implementation Steps

### Step 1: Create Controller Method

```php
public function {module}(){
    return view('pages.{module}.{module}');
}
```

### Step 2: Create Main View Page

Structure:

-   Page header with breadcrumb
-   Alert container
-   Main card with header
-   Livewire list component
-   Livewire form component
-   Guide modal include

### Step 3: Create List Component (Livewire)

Features required:

-   Search functionality
-   Filtering by relevant fields
-   Sorting capabilities
-   Pagination with configurable per_page
-   CRUD operations (Create, Edit, Delete, Toggle Status)
-   Activity logging
-   Transaction handling

Properties:

```php
// Search and Filter
public $search = '';
public $filterField = '';
public $filterStatus = '';
public $sortField = 'id';
public $sortDirection = 'desc';
public $perPage = 25;

// Listeners
protected $listeners = [
    'refresh{ModuleName}List' => '$refresh',
    '{module}Deleted' => '$refresh'
];
```

Methods:

-   `sortBy($field)` - Handle sorting
-   `get{ModuleName}sProperty()` - Get filtered/sorted data
-   `delete{ModuleName}($id)` - Delete with validation
-   `toggleStatus($id)` - Toggle active status

### Step 4: Create List View Template

Components:

-   Search and filter controls
-   Per page selector (25, 50, 100)
-   Add new button
-   Data table with sortable headers
-   Action buttons (Edit, Delete)
-   Pagination
-   Empty state

### Step 5: Create Form Component (Livewire)

Features:

-   Create and Edit modes
-   Real-time validation
-   Modal interface
-   Activity logging
-   Transaction handling

Properties:

```php
// Form fields (based on model)
public $itemId;
public $field1 = '';
public $field2 = '';
// ... other fields

// UI State
public $showModal = false;
public $isEditing = false;

// Listeners
protected $listeners = [
    'open{ModuleName}Modal' => 'openModal',
    'edit{ModuleName}' => 'edit'
];
```

Methods:

-   `openModal()` - Open create modal
-   `edit($id)` - Open edit modal
-   `save()` - Save/update with transaction
-   `closeModal()` - Close modal and reset
-   `resetForm()` - Reset form fields

### Step 6: Create Form View Template

Components:

-   Modal structure
-   Form fields with validation
-   Preview section
-   Save/Cancel buttons
-   Loading states

### Step 7: Create Guide Modal

Content:

-   Module explanation
-   Best practices
-   Field descriptions
-   Usage guidelines

## Database Pattern

### Migration Structure

```php
// Master table
Schema::create('{modules}', function (Blueprint $table) {
    $table->id();
    // Main fields
    $table->string('code')->unique(); // if applicable
    $table->string('name');
    // Status and metadata
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->softDeletes();
    $table->blameable();
});

// Detail table (if needed)
Schema::create('{module}_details', function (Blueprint $table) {
    $table->id();
    $table->foreignId('{module}_id')->constrained();
    // Detail fields
    $table->timestamps();
    $table->softDeletes();
    $table->blameable();
});
```

### Model Structure

```php
use SoftDeletes, Blameable;

protected $fillable = [
    // List all fillable fields
];

protected $casts = [
    // Define field types
    'is_active' => 'boolean',
    'created_at' => 'datetime',
];

// Relationships
public function parent() {
    return $this->belongsTo(Parent::class);
}

public function children() {
    return $this->hasMany(Child::class);
}

// Scopes
public function scopeActive($query) {
    return $query->where('is_active', true);
}
```

## Features Checklist

### List Component Features

-   ✅ Search functionality
-   ✅ Multiple filter options
-   ✅ Sortable columns
-   ✅ Configurable pagination (25, 50, 100)
-   ✅ Bulk operations (if needed)
-   ✅ Status toggle
-   ✅ CRUD operations
-   ✅ Activity logging
-   ✅ Error handling

### Form Component Features

-   ✅ Create/Edit modes
-   ✅ Real-time validation
-   ✅ Modal interface
-   ✅ Preview functionality
-   ✅ Loading states
-   ✅ Activity logging
-   ✅ Error handling

### General Features

-   ✅ Responsive design
-   ✅ Transaction handling
-   ✅ Activity logging
-   ✅ Soft deletes
-   ✅ Blameable trait
-   ✅ Alert system
-   ✅ Guide/Help system

## Naming Conventions

### Files

-   Controller: `{ModuleName}Controller.php`
-   Livewire Components: `{ModuleName}List.php`, `{ModuleName}Form.php`
-   Views: `{module}.blade.php`, `{module}-list.blade.php`, `{module}-form.blade.php`
-   Models: `{ModuleName}.php`

### Methods

-   List: `get{ModuleName}sProperty()`, `delete{ModuleName}()`, `toggle{ModuleName}Status()`
-   Form: `open{ModuleName}Modal()`, `edit{ModuleName}()`, `save{ModuleName}()`

### Events/Listeners

-   `refresh{ModuleName}List`
-   `{module}Deleted`
-   `open{ModuleName}Modal`
-   `edit{ModuleName}`

## Usage Example

For a new "Product" module:

1. Create `ProductController@product()` method
2. Create `pages/product/product.blade.php`
3. Create `ProductList.php` and `ProductForm.php` Livewire components
4. Create corresponding view files
5. Follow the established patterns for search, filter, pagination
6. Implement CRUD operations with proper validation and logging

This pattern ensures consistency across all modules and reduces development time for new features.

## Journal Service Integration

For modules that require automatic journal entry creation, use the `JournalService`:

### Example Usage:

```php
use App\Services\JournalService;

// In your controller or Livewire component
$journalService = new JournalService();

// Create sales journal
$salesJournal = $journalService->createSalesJournal([
    'date' => '2026-01-17',
    'invoice_no' => 'INV-001',
    'customer_name' => 'John Doe',
    'amount' => 1000000,
    'id_period' => 1,
    'auto_post' => false // Set to true to auto-post
]);

// Create custom journal
$customJournal = $journalService->createJournalEntry([
    'journal_date' => '2026-01-17',
    'reference' => 'ADJ-001',
    'description' => 'Adjustment Entry',
    'id_period' => 1,
    'entries' => [
        ['coa_code' => '1101', 'description' => 'Cash increase', 'debit' => 500000, 'credit' => 0],
        ['coa_code' => '3201', 'description' => 'Retained earnings', 'debit' => 0, 'credit' => 500000]
    ]
]);

// Post journal
$journalService->postJournal($customJournal->id);
```

### Available Service Methods:

-   `createJournalEntry($data)` - Create custom journal entry
-   `createSalesJournal($data)` - Create sales transaction journal
-   `createPurchaseJournal($data)` - Create purchase transaction journal
-   `createPaymentJournal($data)` - Create payment transaction journal
-   `postJournal($journalId)` - Post a draft journal entry

## Pagination Configuration

For consistent pagination across all list components, use:

```php
// In your Livewire list component
public $perPage = 25;

// In your view template
<select class="form-select form-select-sm" wire:model.live="perPage">
    <option value="25">25 per page</option>
    <option value="50">50 per page</option>
    <option value="100">100 per page</option>
</select>

// In your query method
return $query->paginate($this->perPage);
```
