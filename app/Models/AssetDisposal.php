<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kuroragi\GeneralHelper\Traits\Blameable;

class AssetDisposal extends Model
{
    use HasFactory, SoftDeletes, Blameable;

    protected $fillable = [
        'asset_id',
        'disposal_date',
        'disposal_method',
        'disposal_amount',
        'book_value_at_disposal',
        'gain_loss',
        'journal_master_id',
        'buyer_info',
        'reason',
        'notes',
    ];

    protected $casts = [
        'disposal_date' => 'date',
        'disposal_amount' => 'integer',
        'book_value_at_disposal' => 'integer',
        'gain_loss' => 'integer',
    ];

    public const METHODS = [
        'sold' => 'Dijual',
        'scrapped' => 'Dihapusbukukan',
        'donated' => 'Didonasikan',
    ];

    // Relationships
    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function journalMaster()
    {
        return $this->belongsTo(JournalMaster::class, 'journal_master_id');
    }
}
