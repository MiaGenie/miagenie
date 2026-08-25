<?php

namespace App\Genie\Data;

use App\Abstracts\GenieData;
use App\Contracts\GenieDataContract;
use App\Models\File;
use App\Models\Vector;

class Competitors extends GenieData implements GenieDataContract
{
    public function getModel(): File|Vector
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
