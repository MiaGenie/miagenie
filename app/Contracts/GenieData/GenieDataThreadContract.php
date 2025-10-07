<?php

namespace App\Contracts\GenieData;

use App\Models\RuleStep;

interface GenieDataThreadContract
{
    /**
     * @return RuleStep
     */
    public function getCurrentStep(): RuleStep;

}
