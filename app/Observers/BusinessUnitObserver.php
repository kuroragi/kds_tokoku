<?php

namespace App\Observers;

use App\Models\BusinessUnit;
use App\Models\CoaTemplate;
use App\Models\Position;
use App\Models\UnitOfMeasure;

class BusinessUnitObserver
{
    /**
     * Handle the BusinessUnit "created" event.
     * Auto-duplicate Position defaults, UnitOfMeasure defaults,
     * and clone COA templates.
     */
    public function created(BusinessUnit $businessUnit): void
    {
        Position::duplicateDefaultsForBusinessUnit($businessUnit->id);
        UnitOfMeasure::duplicateDefaultsForBusinessUnit($businessUnit->id);
        CoaTemplate::cloneToBusinessUnit($businessUnit->id);
    }
}
