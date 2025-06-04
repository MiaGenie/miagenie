<?php

namespace App\GenieData\Thread;

use App\Abstracts\GenieData;
use App\Contracts\GenieDataContract;
use App\Models\Thread;

class Competitors extends GenieData implements GenieDataContract
{

    public function getModel(): \App\Models\File|\App\Models\Vector|\App\Models\Assistant|\App\Models\Thread
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
