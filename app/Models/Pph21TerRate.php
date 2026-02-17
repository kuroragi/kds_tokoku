<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pph21TerRate extends Model
{
    protected $fillable = [
        'category',
        'min_income',
        'max_income',
        'rate',
    ];

    protected $casts = [
        'min_income' => 'integer',
        'max_income' => 'integer',
        'rate' => 'decimal:2',
    ];

    /**
     * Get PPh21 TER category for a PTKP status.
     */
    public static function getCategoryForPtkp(string $ptkpStatus): string
    {
        return match ($ptkpStatus) {
            'TK/0', 'TK/1' => 'A',
            'TK/2', 'TK/3', 'K/0', 'K/1' => 'B',
            'K/2', 'K/3' => 'C',
            default => 'A',
        };
    }

    /**
     * Get TER rate for given category and monthly gross income.
     * Returns percentage rate (e.g., 2.50 for 2.5%).
     */
    public static function getRate(string $category, int $monthlyGrossIncome): float
    {
        $rate = self::where('category', $category)
            ->where('min_income', '<=', $monthlyGrossIncome)
            ->where(function ($q) use ($monthlyGrossIncome) {
                $q->where('max_income', '>=', $monthlyGrossIncome)
                    ->orWhereNull('max_income');
            })
            ->value('rate');

        return (float) ($rate ?? 0);
    }

    /**
     * Calculate PPh21 TER for a given PTKP status and monthly gross income.
     * Returns the tax amount (integer).
     */
    public static function calculateTax(string $ptkpStatus, int $monthlyGrossIncome): int
    {
        $category = self::getCategoryForPtkp($ptkpStatus);
        $rate = self::getRate($category, $monthlyGrossIncome);

        return (int) round($monthlyGrossIncome * $rate / 100);
    }
}
