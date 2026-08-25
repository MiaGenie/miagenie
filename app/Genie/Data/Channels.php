<?php

namespace App\Genie\Data;

use App\Abstracts\GenieData;
use App\Contracts\GenieDataContract;
use App\Models\File;
use App\Models\RuleStep;
use App\Models\Thread;
use App\Models\Vector;

class Channels extends GenieData implements GenieDataContract
{
    private Thread $thread;

    private ?RuleStep $lastStep;

    private ?RuleStep $currentStep;

    public function __construct(
        string $type,
        string $action,
        Thread $thread
    ) {
        parent::__construct($type, $action, $thread);
        $this->type = 'THREAD';
        $this->thread = $thread;
        $this->lastStep = $this->lastStep();
        $this->currentStep = $this->nextStep($this->lastStep);
        $this->data = $this->getData();
    }

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
