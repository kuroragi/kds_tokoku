<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kuroragi\GeneralHelper\Traits\Blameable;

class AssetTransfer extends Model
{
    use HasFactory, SoftDeletes, Blameable;

    protected $fillable = [
        'asset_id',
        'transfer_date',
        'from_location',
        'to_location',
        'from_business_unit_id',
        'to_business_unit_id',
        'reason',
        'notes',
    ];

    protected $casts = [
        'transfer_date' => 'date',
    ];

    // Relationships
    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function fromBusinessUnit()
    {
        return $this->belongsTo(BusinessUnit::class, 'from_business_unit_id');
    }

    public function toBusinessUnit()
    {
        return $this->belongsTo(BusinessUnit::class, 'to_business_unit_id');
    }
}
