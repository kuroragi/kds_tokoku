<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kuroragi\GeneralHelper\Traits\Blameable;

class LossCompensation extends Model
{
    use SoftDeletes, Blameable;

    protected $table = 'loss_compensations';

    protected $fillable = [
        'source_year',
        'original_amount',
        'used_amount',
        'remaining_amount',
        'expires_year',
    ];

    protected $casts = [
        'source_year' => 'integer',
        'original_amount' => 'integer',
        'used_amount' => 'integer',
        'remaining_amount' => 'integer',
        'expires_year' => 'integer',
    ];

    // Scopes
    public function scopeAvailable($query, int $forYear)
    {
        return $query->where('remaining_amount', '>', 0)
            ->where('expires_year', '>=', $forYear);
    }

    public function scopeForSourceYear($query, int $year)
    {
        return $query->where('source_year', $year);
    }

    // Helpers
    public function isExpired(int $currentYear = null): bool
    {
        $currentYear = $currentYear ?? (int) date('Y');
        return $this->expires_year < $currentYear;
    }

    public function applyCompensation(int $amount): int
    {
        $applicable = min($amount, $this->remaining_amount);
        $this->used_amount += $applicable;
        $this->remaining_amount -= $applicable;
        $this->save();
        return $applicable;
    }
}
