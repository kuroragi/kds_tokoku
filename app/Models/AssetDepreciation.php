<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kuroragi\GeneralHelper\Traits\Blameable;

class AssetDepreciation extends Model
{
    use HasFactory, SoftDeletes, Blameable;

    protected $fillable = [
        'asset_id',
        'period_id',
        'depreciation_date',
        'depreciation_amount',
        'accumulated_depreciation',
        'book_value',
        'journal_master_id',
        'notes',
    ];

    protected $casts = [
        'depreciation_date' => 'date',
        'depreciation_amount' => 'integer',
        'accumulated_depreciation' => 'integer',
        'book_value' => 'integer',
    ];

    // Relationships
    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function period()
    {
        return $this->belongsTo(Period::class);
    }

    public function journalMaster()
    {
        return $this->belongsTo(JournalMaster::class, 'journal_master_id');
    }
}
