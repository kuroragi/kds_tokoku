<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kuroragi\GeneralHelper\Traits\Blameable;

class ProjectRevenue extends Model
{
    use HasFactory, SoftDeletes, Blameable;

    protected $fillable = [
        'project_id',
        'revenue_date',
        'description',
        'amount',
        'journal_master_id',
        'sale_id',
        'notes',
    ];

    protected $casts = [
        'revenue_date' => 'date',
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

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}
