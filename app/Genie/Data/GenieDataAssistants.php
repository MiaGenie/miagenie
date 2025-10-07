<?php

namespace App\Genie\Data;

use App\Abstracts\GenieData;
use App\Contracts\GenieDataContract;
use App\Enums\GenieSyncAction;
use App\Models\Assistant;

class GenieDataAssistants extends GenieData implements GenieDataContract
{

    /**
     * @var Assistant
     */
    private Assistant $assistant;

    /**
     * @var GenieSyncAction
     */
    protected GenieSyncAction $action;

    /**
     * @param Assistant $assistant
     * @param GenieSyncAction $action
     */
    public function __construct(
        Assistant $assistant,
        GenieSyncAction $action,
    ) {
        parent::__construct($assistant, $action);
        $this->assistant = $assistant;
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
