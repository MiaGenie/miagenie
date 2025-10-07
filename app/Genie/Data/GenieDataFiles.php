<?php

namespace App\Genie\Data;

use App\Abstracts\GenieData;
use App\Contracts\GenieDataContract;
use App\Enums\GenieSyncAction;
use App\Models\File;

class GenieDataFiles extends GenieData implements GenieDataContract
{

    /**
     * @var File
     */
    private File $file;

    /**
     * @var GenieSyncAction
     */
    protected GenieSyncAction $action;

    /**
     * @param File $file
     * @param GenieSyncAction $action
     */
    public function __construct(
        File $file,
        GenieSyncAction $action,
    ) {
        parent::__construct($file, $action);
        $this->file = $file;
        $this->action = $action;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        $data = match ($this->action) {
            GenieSyncAction::CREATE => [
                            'file' => fopen($this->file->getFullPath(), 'r'),
                            'purpose' => 'assistants'
                        ]
        };

        return $data;
    }


    public function getRequest(): array
    {
        $request = match ($this->action) {
            GenieSyncAction::CREATE => $this->getData(),
            GenieSyncAction::DELETE => [$this->getModelProviderId()]
        };

        return $request;
    }

}
