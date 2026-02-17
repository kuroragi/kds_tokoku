<?php

namespace Database\Seeders;

use App\Models\Pph21TerRate;
use Illuminate\Database\Seeder;

class Pph21TerRateSeeder extends Seeder
{
    /**
     * Seed PPh21 TER rates based on PP 58/2023 and PMK 168/2023.
     *
     * Category A: TK/0, TK/1
     * Category B: TK/2, TK/3, K/0, K/1
     * Category C: K/2, K/3
     */
    public function run(): void
    {
        Pph21TerRate::truncate();

        $rates = array_merge(
            $this->getCategoryA(),
            $this->getCategoryB(),
            $this->getCategoryC()
        );

        foreach ($rates as $rate) {
            Pph21TerRate::create($rate);
        }
    }

    protected function getCategoryA(): array
    {
        return [
            ['category' => 'A', 'min_income' => 0, 'max_income' => 5400000, 'rate' => 0.00],
            ['category' => 'A', 'min_income' => 5400001, 'max_income' => 5650000, 'rate' => 0.25],
            ['category' => 'A', 'min_income' => 5650001, 'max_income' => 5950000, 'rate' => 0.50],
            ['category' => 'A', 'min_income' => 5950001, 'max_income' => 6300000, 'rate' => 0.75],
            ['category' => 'A', 'min_income' => 6300001, 'max_income' => 6750000, 'rate' => 1.00],
            ['category' => 'A', 'min_income' => 6750001, 'max_income' => 7500000, 'rate' => 1.25],
            ['category' => 'A', 'min_income' => 7500001, 'max_income' => 8550000, 'rate' => 1.50],
            ['category' => 'A', 'min_income' => 8550001, 'max_income' => 9650000, 'rate' => 1.75],
            ['category' => 'A', 'min_income' => 9650001, 'max_income' => 10050000, 'rate' => 2.00],
            ['category' => 'A', 'min_income' => 10050001, 'max_income' => 10350000, 'rate' => 2.25],
            ['category' => 'A', 'min_income' => 10350001, 'max_income' => 10700000, 'rate' => 2.50],
            ['category' => 'A', 'min_income' => 10700001, 'max_income' => 11050000, 'rate' => 3.00],
            ['category' => 'A', 'min_income' => 11050001, 'max_income' => 11600000, 'rate' => 3.50],
            ['category' => 'A', 'min_income' => 11600001, 'max_income' => 12500000, 'rate' => 4.00],
            ['category' => 'A', 'min_income' => 12500001, 'max_income' => 13750000, 'rate' => 5.00],
            ['category' => 'A', 'min_income' => 13750001, 'max_income' => 15100000, 'rate' => 6.00],
            ['category' => 'A', 'min_income' => 15100001, 'max_income' => 16950000, 'rate' => 7.00],
            ['category' => 'A', 'min_income' => 16950001, 'max_income' => 19750000, 'rate' => 8.00],
            ['category' => 'A', 'min_income' => 19750001, 'max_income' => 24150000, 'rate' => 9.00],
            ['category' => 'A', 'min_income' => 24150001, 'max_income' => 26450000, 'rate' => 10.00],
            ['category' => 'A', 'min_income' => 26450001, 'max_income' => 28000000, 'rate' => 11.00],
            ['category' => 'A', 'min_income' => 28000001, 'max_income' => 30050000, 'rate' => 12.00],
            ['category' => 'A', 'min_income' => 30050001, 'max_income' => 32400000, 'rate' => 13.00],
            ['category' => 'A', 'min_income' => 32400001, 'max_income' => 35400000, 'rate' => 14.00],
            ['category' => 'A', 'min_income' => 35400001, 'max_income' => 39100000, 'rate' => 15.00],
            ['category' => 'A', 'min_income' => 39100001, 'max_income' => 43850000, 'rate' => 16.00],
            ['category' => 'A', 'min_income' => 43850001, 'max_income' => 47800000, 'rate' => 17.00],
            ['category' => 'A', 'min_income' => 47800001, 'max_income' => 51400000, 'rate' => 18.00],
            ['category' => 'A', 'min_income' => 51400001, 'max_income' => 56300000, 'rate' => 19.00],
            ['category' => 'A', 'min_income' => 56300001, 'max_income' => 62200000, 'rate' => 20.00],
            ['category' => 'A', 'min_income' => 62200001, 'max_income' => 68600000, 'rate' => 21.00],
            ['category' => 'A', 'min_income' => 68600001, 'max_income' => 77500000, 'rate' => 22.00],
            ['category' => 'A', 'min_income' => 77500001, 'max_income' => 89000000, 'rate' => 23.00],
            ['category' => 'A', 'min_income' => 89000001, 'max_income' => 103000000, 'rate' => 24.00],
            ['category' => 'A', 'min_income' => 103000001, 'max_income' => 125000000, 'rate' => 25.00],
            ['category' => 'A', 'min_income' => 125000001, 'max_income' => 157000000, 'rate' => 26.00],
            ['category' => 'A', 'min_income' => 157000001, 'max_income' => 206000000, 'rate' => 27.00],
            ['category' => 'A', 'min_income' => 206000001, 'max_income' => 337000000, 'rate' => 28.00],
            ['category' => 'A', 'min_income' => 337000001, 'max_income' => 454000000, 'rate' => 29.00],
            ['category' => 'A', 'min_income' => 454000001, 'max_income' => 550000000, 'rate' => 30.00],
            ['category' => 'A', 'min_income' => 550000001, 'max_income' => 695000000, 'rate' => 31.00],
            ['category' => 'A', 'min_income' => 695000001, 'max_income' => 910000000, 'rate' => 32.00],
            ['category' => 'A', 'min_income' => 910000001, 'max_income' => 1400000000, 'rate' => 33.00],
            ['category' => 'A', 'min_income' => 1400000001, 'max_income' => null, 'rate' => 34.00],
        ];
    }

    protected function getCategoryB(): array
    {
        return [
            ['category' => 'B', 'min_income' => 0, 'max_income' => 6200000, 'rate' => 0.00],
            ['category' => 'B', 'min_income' => 6200001, 'max_income' => 6500000, 'rate' => 0.25],
            ['category' => 'B', 'min_income' => 6500001, 'max_income' => 6850000, 'rate' => 0.50],
            ['category' => 'B', 'min_income' => 6850001, 'max_income' => 7300000, 'rate' => 0.75],
            ['category' => 'B', 'min_income' => 7300001, 'max_income' => 9200000, 'rate' => 1.00],
            ['category' => 'B', 'min_income' => 9200001, 'max_income' => 10750000, 'rate' => 1.50],
            ['category' => 'B', 'min_income' => 10750001, 'max_income' => 11250000, 'rate' => 2.00],
            ['category' => 'B', 'min_income' => 11250001, 'max_income' => 11600000, 'rate' => 2.50],
            ['category' => 'B', 'min_income' => 11600001, 'max_income' => 12500000, 'rate' => 3.00],
            ['category' => 'B', 'min_income' => 12500001, 'max_income' => 13750000, 'rate' => 4.00],
            ['category' => 'B', 'min_income' => 13750001, 'max_income' => 15100000, 'rate' => 5.00],
            ['category' => 'B', 'min_income' => 15100001, 'max_income' => 16950000, 'rate' => 6.00],
            ['category' => 'B', 'min_income' => 16950001, 'max_income' => 19750000, 'rate' => 7.00],
            ['category' => 'B', 'min_income' => 19750001, 'max_income' => 24150000, 'rate' => 8.00],
            ['category' => 'B', 'min_income' => 24150001, 'max_income' => 26450000, 'rate' => 9.00],
            ['category' => 'B', 'min_income' => 26450001, 'max_income' => 28000000, 'rate' => 10.00],
            ['category' => 'B', 'min_income' => 28000001, 'max_income' => 30050000, 'rate' => 11.00],
            ['category' => 'B', 'min_income' => 30050001, 'max_income' => 32400000, 'rate' => 12.00],
            ['category' => 'B', 'min_income' => 32400001, 'max_income' => 35400000, 'rate' => 13.00],
            ['category' => 'B', 'min_income' => 35400001, 'max_income' => 39100000, 'rate' => 14.00],
            ['category' => 'B', 'min_income' => 39100001, 'max_income' => 43850000, 'rate' => 15.00],
            ['category' => 'B', 'min_income' => 43850001, 'max_income' => 47800000, 'rate' => 16.00],
            ['category' => 'B', 'min_income' => 47800001, 'max_income' => 51400000, 'rate' => 17.00],
            ['category' => 'B', 'min_income' => 51400001, 'max_income' => 56300000, 'rate' => 18.00],
            ['category' => 'B', 'min_income' => 56300001, 'max_income' => 62200000, 'rate' => 19.00],
            ['category' => 'B', 'min_income' => 62200001, 'max_income' => 68600000, 'rate' => 20.00],
            ['category' => 'B', 'min_income' => 68600001, 'max_income' => 77500000, 'rate' => 21.00],
            ['category' => 'B', 'min_income' => 77500001, 'max_income' => 89000000, 'rate' => 22.00],
            ['category' => 'B', 'min_income' => 89000001, 'max_income' => 103000000, 'rate' => 23.00],
            ['category' => 'B', 'min_income' => 103000001, 'max_income' => 125000000, 'rate' => 24.00],
            ['category' => 'B', 'min_income' => 125000001, 'max_income' => 157000000, 'rate' => 25.00],
            ['category' => 'B', 'min_income' => 157000001, 'max_income' => 206000000, 'rate' => 26.00],
            ['category' => 'B', 'min_income' => 206000001, 'max_income' => 337000000, 'rate' => 27.00],
            ['category' => 'B', 'min_income' => 337000001, 'max_income' => 454000000, 'rate' => 28.00],
            ['category' => 'B', 'min_income' => 454000001, 'max_income' => 550000000, 'rate' => 29.00],
            ['category' => 'B', 'min_income' => 550000001, 'max_income' => 695000000, 'rate' => 30.00],
            ['category' => 'B', 'min_income' => 695000001, 'max_income' => 910000000, 'rate' => 31.00],
            ['category' => 'B', 'min_income' => 910000001, 'max_income' => 1400000000, 'rate' => 32.00],
            ['category' => 'B', 'min_income' => 1400000001, 'max_income' => null, 'rate' => 33.00],
        ];
    }

    protected function getCategoryC(): array
    {
        return [
            ['category' => 'C', 'min_income' => 0, 'max_income' => 6600000, 'rate' => 0.00],
            ['category' => 'C', 'min_income' => 6600001, 'max_income' => 6950000, 'rate' => 0.25],
            ['category' => 'C', 'min_income' => 6950001, 'max_income' => 7350000, 'rate' => 0.50],
            ['category' => 'C', 'min_income' => 7350001, 'max_income' => 7800000, 'rate' => 0.75],
            ['category' => 'C', 'min_income' => 7800001, 'max_income' => 8850000, 'rate' => 1.00],
            ['category' => 'C', 'min_income' => 8850001, 'max_income' => 9800000, 'rate' => 1.25],
            ['category' => 'C', 'min_income' => 9800001, 'max_income' => 10950000, 'rate' => 1.50],
            ['category' => 'C', 'min_income' => 10950001, 'max_income' => 11200000, 'rate' => 1.75],
            ['category' => 'C', 'min_income' => 11200001, 'max_income' => 12050000, 'rate' => 2.00],
            ['category' => 'C', 'min_income' => 12050001, 'max_income' => 12950000, 'rate' => 3.00],
            ['category' => 'C', 'min_income' => 12950001, 'max_income' => 14150000, 'rate' => 4.00],
            ['category' => 'C', 'min_income' => 14150001, 'max_income' => 15550000, 'rate' => 5.00],
            ['category' => 'C', 'min_income' => 15550001, 'max_income' => 17050000, 'rate' => 6.00],
            ['category' => 'C', 'min_income' => 17050001, 'max_income' => 19500000, 'rate' => 7.00],
            ['category' => 'C', 'min_income' => 19500001, 'max_income' => 22700000, 'rate' => 8.00],
            ['category' => 'C', 'min_income' => 22700001, 'max_income' => 26600000, 'rate' => 9.00],
            ['category' => 'C', 'min_income' => 26600001, 'max_income' => 28100000, 'rate' => 10.00],
            ['category' => 'C', 'min_income' => 28100001, 'max_income' => 30100000, 'rate' => 11.00],
            ['category' => 'C', 'min_income' => 30100001, 'max_income' => 32600000, 'rate' => 12.00],
            ['category' => 'C', 'min_income' => 32600001, 'max_income' => 35400000, 'rate' => 13.00],
            ['category' => 'C', 'min_income' => 35400001, 'max_income' => 38900000, 'rate' => 14.00],
            ['category' => 'C', 'min_income' => 38900001, 'max_income' => 43000000, 'rate' => 15.00],
            ['category' => 'C', 'min_income' => 43000001, 'max_income' => 47000000, 'rate' => 16.00],
            ['category' => 'C', 'min_income' => 47000001, 'max_income' => 51000000, 'rate' => 17.00],
            ['category' => 'C', 'min_income' => 51000001, 'max_income' => 55800000, 'rate' => 18.00],
            ['category' => 'C', 'min_income' => 55800001, 'max_income' => 62000000, 'rate' => 19.00],
            ['category' => 'C', 'min_income' => 62000001, 'max_income' => 68600000, 'rate' => 20.00],
            ['category' => 'C', 'min_income' => 68600001, 'max_income' => 77500000, 'rate' => 21.00],
            ['category' => 'C', 'min_income' => 77500001, 'max_income' => 89000000, 'rate' => 22.00],
            ['category' => 'C', 'min_income' => 89000001, 'max_income' => 103000000, 'rate' => 23.00],
            ['category' => 'C', 'min_income' => 103000001, 'max_income' => 125000000, 'rate' => 24.00],
            ['category' => 'C', 'min_income' => 125000001, 'max_income' => 157000000, 'rate' => 25.00],
            ['category' => 'C', 'min_income' => 157000001, 'max_income' => 206000000, 'rate' => 26.00],
            ['category' => 'C', 'min_income' => 206000001, 'max_income' => 337000000, 'rate' => 27.00],
            ['category' => 'C', 'min_income' => 337000001, 'max_income' => 454000000, 'rate' => 28.00],
            ['category' => 'C', 'min_income' => 454000001, 'max_income' => 550000000, 'rate' => 29.00],
            ['category' => 'C', 'min_income' => 550000001, 'max_income' => 695000000, 'rate' => 30.00],
            ['category' => 'C', 'min_income' => 695000001, 'max_income' => 910000000, 'rate' => 31.00],
            ['category' => 'C', 'min_income' => 910000001, 'max_income' => 1400000000, 'rate' => 32.00],
            ['category' => 'C', 'min_income' => 1400000001, 'max_income' => null, 'rate' => 33.00],
        ];
    }
}
