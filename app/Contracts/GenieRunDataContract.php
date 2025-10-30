<?php

namespace App\Contracts;

use App\Enums\GenieSyncAction;
use App\Models\RuleStep;
use App\Models\Run;

interface GenieRunDataContract
{
    /**
     * @return GenieSyncAction
     */
    public function getAction(): GenieSyncAction;

    /**
     * @return Run
     */
    public function getModel(): Run;

    /**
     * @param ?RuleStep $lastStep
     * @return ?RuleStep
     */
    public function nextStep(?RuleStep $lastStep = null): ?RuleStep;

}
