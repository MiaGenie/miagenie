<?php

namespace App\Contracts;

use App\Enums\RuleType;

/**
 * The surface a rule step's data builder exposes to the transport.
 *
 * Kept separate from GenieDataContract because the file and vector sync paths share that
 * contract but have no prompt, locale or rule type.
 */
interface GenieStepDataContract
{
    public function getLocale(): string;

    public function getPrompt(): string;

    public function getRuleType(): RuleType;
}
