<?php

namespace App\Genie\Data;

use App\Abstracts\GenieData;
use App\Contracts\GenieDataContract;
use App\Models\RuleStep;
use App\Models\Thread;

class Channels extends GenieData implements GenieDataContract
{
    /**
     * @var Thread
     */
    private Thread $thread;

    /**
     * @var ?RuleStep
     */
    private ?RuleStep $lastStep;

    /**
     * @var ?RuleStep
     */
    private ?RuleStep $currentStep;

    /**
     * @param string $type
     * @param string $action
     * @param Thread $thread
     */
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
