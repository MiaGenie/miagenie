<?php

namespace App\Genie\Data;

use App\Abstracts\GenieData;
use App\Contracts\GenieDataContract;
use App\Enums\GenieSyncAction;
use App\Models\Vector;

class GenieDataVectors extends GenieData implements GenieDataContract
{

    /**
     * @var Vector
     */
    private Vector $vector;

    /**
     * @var GenieSyncAction
     */
    protected GenieSyncAction $action;

    /**
     * @param Vector $vector
     * @param GenieSyncAction $action
     */
    public function __construct(
        Vector $vector,
        GenieSyncAction $action,
    ) {
        parent::__construct($vector, $action);
        $this->vector = $vector;
        $this->action = $action;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        $data = [];

        return $data;
    }


    public function getRequest(): array
    {
        $request = [];

        return $request;
    }

}
