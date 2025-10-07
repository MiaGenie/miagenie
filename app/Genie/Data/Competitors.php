<?php

namespace App\Genie\Data;

use App\Abstracts\GenieData;
use App\Contracts\GenieDataContract;

class Competitors extends GenieData implements GenieDataContract
{

    public function getModel(): \App\Models\File|\App\Models\Vector|\App\Models\Assistant
    {
        // TODO: Implement getModel() method.
    }

    public function getData(): array
    {
        // TODO: Implement getData() method.
    }

    public function getRequest(): array
    {
        // TODO: Implement getRequest() method.
    }
}
