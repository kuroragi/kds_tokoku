<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kuroragi\GeneralHelper\Traits\Blameable;

class FiscalCorrection extends Model
{
    use SoftDeletes, Blameable;

    protected $fillable = [
        'year',
        'description',
        'correction_type',
        'category',
        'amount',
        'notes',
    ];

    protected $casts = [
        'year' => 'integer',
        'amount' => 'integer',
    ];

    // Scopes
    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    public function scopePositive($query)
    {
        return $query->where('correction_type', 'positive');
    }

    public function scopeNegative($query)
    {
        return $query->where('correction_type', 'negative');
    }

    public function scopeBedaTetap($query)
    {
        return $query->where('category', 'beda_tetap');
    }

    public function scopeBedaWaktu($query)
    {
        return $query->where('category', 'beda_waktu');
    }
}
