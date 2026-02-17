<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kuroragi\GeneralHelper\Traits\Blameable;

class SalaryComponent extends Model
{
    use SoftDeletes, Blameable;

    protected $fillable = [
        'business_unit_id',
        'code',
        'name',
        'type',
        'category',
        'apply_method',
        'calculation_type',
        'employee_field_name',
        'setting_key',
        'percentage_base',
        'default_amount',
        'is_taxable',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'default_amount' => 'integer',
        'is_taxable' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    const TYPES = [
        'earning' => 'Penghasilan',
        'deduction' => 'Potongan',
        'benefit' => 'Beban Perusahaan',
    ];

    const CATEGORIES = [
        'gaji_pokok' => 'Gaji Pokok',
        'tunjangan_tetap' => 'Tunjangan Tetap',
        'tunjangan_tidak_tetap' => 'Tunjangan Tidak Tetap',
        'bpjs' => 'BPJS',
        'lembur' => 'Lembur',
        'potongan' => 'Potongan',
        'pinjaman' => 'Pinjaman',
        'pph21' => 'PPh 21',
    ];

    const APPLY_METHODS = [
        'auto' => 'Otomatis',
        'template' => 'Template',
        'manual' => 'Manual',
    ];

    const CALCULATION_TYPES = [
        'fixed' => 'Nominal Tetap',
        'percentage' => 'Persentase',
        'employee_field' => 'Field Karyawan',
    ];

    // Relationships
    public function businessUnit()
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    public function positionSalaryComponents()
    {
        return $this->hasMany(PositionSalaryComponent::class);
    }

    public function employeeSalaryComponents()
    {
        return $this->hasMany(EmployeeSalaryComponent::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByBusinessUnit($query, $businessUnitId)
    {
        return $query->where('business_unit_id', $businessUnitId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByApplyMethod($query, string $method)
    {
        return $query->where('apply_method', $method);
    }

    public function scopeAuto($query)
    {
        return $query->where('apply_method', 'auto');
    }

    public function scopeTemplate($query)
    {
        return $query->where('apply_method', 'template');
    }

    public function scopeManual($query)
    {
        return $query->where('apply_method', 'manual');
    }

    /**
     * System default salary components that will be seeded for each business unit
     */
    public static function getSystemDefaults(): array
    {
        return [
            // EARNINGS
            ['code' => 'GP', 'name' => 'Gaji Pokok', 'type' => 'earning', 'category' => 'gaji_pokok', 'apply_method' => 'auto', 'calculation_type' => 'employee_field', 'employee_field_name' => 'base_salary', 'is_taxable' => true, 'sort_order' => 1],
            ['code' => 'TJ-TRP', 'name' => 'Tunjangan Transport', 'type' => 'earning', 'category' => 'tunjangan_tetap', 'apply_method' => 'template', 'calculation_type' => 'fixed', 'default_amount' => 0, 'is_taxable' => true, 'sort_order' => 10],
            ['code' => 'TJ-MKN', 'name' => 'Tunjangan Makan', 'type' => 'earning', 'category' => 'tunjangan_tetap', 'apply_method' => 'template', 'calculation_type' => 'fixed', 'default_amount' => 0, 'is_taxable' => true, 'sort_order' => 11],
            ['code' => 'TJ-KOM', 'name' => 'Tunjangan Komunikasi', 'type' => 'earning', 'category' => 'tunjangan_tetap', 'apply_method' => 'template', 'calculation_type' => 'fixed', 'default_amount' => 0, 'is_taxable' => true, 'sort_order' => 12],
            ['code' => 'LEMBUR', 'name' => 'Lembur', 'type' => 'earning', 'category' => 'lembur', 'apply_method' => 'manual', 'calculation_type' => 'fixed', 'is_taxable' => true, 'sort_order' => 20],

            // BENEFITS (company expense)
            ['code' => 'BPJS-KES-C', 'name' => 'BPJS Kesehatan (Perusahaan)', 'type' => 'benefit', 'category' => 'bpjs', 'apply_method' => 'auto', 'calculation_type' => 'percentage', 'setting_key' => 'bpjs_kes_company_rate', 'percentage_base' => 'gaji_pokok', 'is_taxable' => false, 'sort_order' => 30],
            ['code' => 'BPJS-JKK', 'name' => 'BPJS JKK', 'type' => 'benefit', 'category' => 'bpjs', 'apply_method' => 'auto', 'calculation_type' => 'percentage', 'setting_key' => 'bpjs_jkk_rate', 'percentage_base' => 'gaji_pokok', 'is_taxable' => false, 'sort_order' => 31],
            ['code' => 'BPJS-JKM', 'name' => 'BPJS JKM', 'type' => 'benefit', 'category' => 'bpjs', 'apply_method' => 'auto', 'calculation_type' => 'percentage', 'setting_key' => 'bpjs_jkm_rate', 'percentage_base' => 'gaji_pokok', 'is_taxable' => false, 'sort_order' => 32],
            ['code' => 'BPJS-JHT-C', 'name' => 'BPJS JHT (Perusahaan)', 'type' => 'benefit', 'category' => 'bpjs', 'apply_method' => 'auto', 'calculation_type' => 'percentage', 'setting_key' => 'bpjs_jht_company_rate', 'percentage_base' => 'gaji_pokok', 'is_taxable' => false, 'sort_order' => 33],
            ['code' => 'BPJS-JP-C', 'name' => 'BPJS JP (Perusahaan)', 'type' => 'benefit', 'category' => 'bpjs', 'apply_method' => 'auto', 'calculation_type' => 'percentage', 'setting_key' => 'bpjs_jp_company_rate', 'percentage_base' => 'gaji_pokok', 'is_taxable' => false, 'sort_order' => 34],

            // DEDUCTIONS (from employee)
            ['code' => 'BPJS-KES-E', 'name' => 'BPJS Kesehatan (Karyawan)', 'type' => 'deduction', 'category' => 'bpjs', 'apply_method' => 'auto', 'calculation_type' => 'percentage', 'setting_key' => 'bpjs_kes_employee_rate', 'percentage_base' => 'gaji_pokok', 'is_taxable' => false, 'sort_order' => 40],
            ['code' => 'BPJS-JHT-E', 'name' => 'BPJS JHT (Karyawan)', 'type' => 'deduction', 'category' => 'bpjs', 'apply_method' => 'auto', 'calculation_type' => 'percentage', 'setting_key' => 'bpjs_jht_employee_rate', 'percentage_base' => 'gaji_pokok', 'is_taxable' => false, 'sort_order' => 41],
            ['code' => 'BPJS-JP-E', 'name' => 'BPJS JP (Karyawan)', 'type' => 'deduction', 'category' => 'bpjs', 'apply_method' => 'auto', 'calculation_type' => 'percentage', 'setting_key' => 'bpjs_jp_employee_rate', 'percentage_base' => 'gaji_pokok', 'is_taxable' => false, 'sort_order' => 42],

            // OTHER DEDUCTIONS
            ['code' => 'POT-TELAT', 'name' => 'Potongan Keterlambatan', 'type' => 'deduction', 'category' => 'potongan', 'apply_method' => 'manual', 'calculation_type' => 'fixed', 'is_taxable' => false, 'sort_order' => 50],
            ['code' => 'POT-ABSEN', 'name' => 'Potongan Tidak Masuk', 'type' => 'deduction', 'category' => 'potongan', 'apply_method' => 'manual', 'calculation_type' => 'fixed', 'is_taxable' => false, 'sort_order' => 51],
            ['code' => 'POT-LAIN', 'name' => 'Potongan Lain-lain', 'type' => 'deduction', 'category' => 'potongan', 'apply_method' => 'manual', 'calculation_type' => 'fixed', 'is_taxable' => false, 'sort_order' => 52],

            // LOAN DEDUCTION
            ['code' => 'POT-PINJAMAN', 'name' => 'Potongan Pinjaman/Kasbon', 'type' => 'deduction', 'category' => 'pinjaman', 'apply_method' => 'auto', 'calculation_type' => 'fixed', 'is_taxable' => false, 'sort_order' => 55],

            // PPH21 (active — controlled by pph21_enabled setting)
            ['code' => 'PPH21', 'name' => 'PPh 21', 'type' => 'deduction', 'category' => 'pph21', 'apply_method' => 'auto', 'calculation_type' => 'percentage', 'is_taxable' => false, 'sort_order' => 60],
        ];
    }

    /**
     * Seed default salary components for a business unit.
     */
    public static function seedDefaultsForBusinessUnit(int $businessUnitId): void
    {
        foreach (self::getSystemDefaults() as $default) {
            $exists = self::where('business_unit_id', $businessUnitId)
                ->where('code', $default['code'])
                ->exists();

            if (!$exists) {
                self::create(array_merge($default, ['business_unit_id' => $businessUnitId]));
            }
        }
    }
}
