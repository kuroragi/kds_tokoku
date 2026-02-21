<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kuroragi\GeneralHelper\Traits\Blameable;

class ProjectCost extends Model
{
    use HasFactory, SoftDeletes, Blameable;

    protected $fillable = [
        'project_id',
        'cost_date',
        'category',
        'description',
        'amount',
        'journal_master_id',
        'purchase_id',
        'notes',
    ];

    protected $casts = [
        'cost_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function journalMaster()
    {
        return $this->belongsTo(JournalMaster::class);
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }
}
