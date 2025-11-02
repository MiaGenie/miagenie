<?php

namespace App\Concerns\Controller;

trait GenieFields
{

    public function getGenieFields(): array
    {
        return is_array($this->genie_fields) ? $this->genie_fields : [];
    }
}
