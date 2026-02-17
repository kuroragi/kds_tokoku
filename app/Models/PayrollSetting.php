<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollSetting extends Model
{
    protected $fillable = [
        'business_unit_id',
        'key',
        'value',
        'label',
        'description',
        'type',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function businessUnit()
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    // Scopes
    public function scopeByBusinessUnit($query, $businessUnitId)
    {
        return $query->where('business_unit_id', $businessUnitId);
    }

    /**
     * Get typed value.
     */
    public function getTypedValueAttribute()
    {
        return match ($this->type) {
            'percentage' => (float) $this->value,
            'amount' => (int) $this->value,
            'boolean' => (bool) $this->value,
            default => $this->value,
        };
    }

    /**
     * System default payroll settings with BPJS rates.
     */
    public static function getDefaults(): array
    {
        return [
            ['key' => 'bpjs_kes_company_rate', 'value' => '4', 'label' => 'BPJS Kesehatan (Perusahaan)', 'description' => 'Persentase BPJS Kesehatan ditanggung perusahaan', 'type' => 'percentage', 'sort_order' => 1],
            ['key' => 'bpjs_kes_employee_rate', 'value' => '1', 'label' => 'BPJS Kesehatan (Karyawan)', 'description' => 'Persentase BPJS Kesehatan ditanggung karyawan', 'type' => 'percentage', 'sort_order' => 2],
            ['key' => 'bpjs_kes_cap', 'value' => '12000000', 'label' => 'Batas Gaji BPJS Kesehatan', 'description' => 'Batas maksimal gaji untuk perhitungan BPJS Kesehatan', 'type' => 'amount', 'sort_order' => 3],
            ['key' => 'bpjs_jkk_rate', 'value' => '0.24', 'label' => 'BPJS JKK', 'description' => 'Persentase BPJS Kecelakaan Kerja (Tingkat I)', 'type' => 'percentage', 'sort_order' => 4],
            ['key' => 'bpjs_jkm_rate', 'value' => '0.3', 'label' => 'BPJS JKM', 'description' => 'Persentase BPJS Jaminan Kematian', 'type' => 'percentage', 'sort_order' => 5],
            ['key' => 'bpjs_jht_company_rate', 'value' => '3.7', 'label' => 'BPJS JHT (Perusahaan)', 'description' => 'Persentase BPJS Jaminan Hari Tua ditanggung perusahaan', 'type' => 'percentage', 'sort_order' => 6],
            ['key' => 'bpjs_jht_employee_rate', 'value' => '2', 'label' => 'BPJS JHT (Karyawan)', 'description' => 'Persentase BPJS Jaminan Hari Tua ditanggung karyawan', 'type' => 'percentage', 'sort_order' => 7],
            ['key' => 'bpjs_jp_company_rate', 'value' => '2', 'label' => 'BPJS JP (Perusahaan)', 'description' => 'Persentase BPJS Jaminan Pensiun ditanggung perusahaan', 'type' => 'percentage', 'sort_order' => 8],
            ['key' => 'bpjs_jp_employee_rate', 'value' => '1', 'label' => 'BPJS JP (Karyawan)', 'description' => 'Persentase BPJS Jaminan Pensiun ditanggung karyawan', 'type' => 'percentage', 'sort_order' => 9],
            ['key' => 'bpjs_jp_cap', 'value' => '10042300', 'label' => 'Batas Gaji BPJS JP', 'description' => 'Batas maksimal gaji untuk perhitungan BPJS Jaminan Pensiun', 'type' => 'amount', 'sort_order' => 10],
            ['key' => 'pph21_enabled', 'value' => '0', 'label' => 'Aktifkan PPh 21', 'description' => 'Aktifkan perhitungan PPh 21 TER pada payroll', 'type' => 'boolean', 'sort_order' => 11],
        ];
    }

    /**
     * Seed default payroll settings for a business unit.
     */
    public static function seedDefaultsForBusinessUnit(int $businessUnitId): void
    {
        foreach (self::getDefaults() as $default) {
            $exists = self::where('business_unit_id', $businessUnitId)
                ->where('key', $default['key'])
                ->exists();

            if (!$exists) {
                self::create(array_merge($default, ['business_unit_id' => $businessUnitId]));
            }
        }
    }

    /**
     * Get setting value by key for a business unit.
     */
    public static function getValue(int $businessUnitId, string $key, $default = null)
    {
        $setting = self::where('business_unit_id', $businessUnitId)
            ->where('key', $key)
            ->first();

        return $setting ? $setting->typed_value : $default;
    }
}
