<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kuroragi\GeneralHelper\Traits\Blameable;

class AssetRepair extends Model
{
    use HasFactory, SoftDeletes, Blameable;

    protected $fillable = [
        'asset_id',
        'vendor_id',
        'repair_date',
        'description',
        'cost',
        'status',
        'completed_date',
        'notes',
    ];

    protected $casts = [
        'repair_date' => 'date',
        'completed_date' => 'date',
        'cost' => 'integer',
    ];

    public const STATUSES = [
        'pending' => 'Menunggu',
        'in_progress' => 'Dalam Proses',
        'completed' => 'Selesai',
    ];

    // Relationships
    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
}
