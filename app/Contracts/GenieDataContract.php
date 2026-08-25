<?php

namespace App\Contracts;

use App\Enums\GenieSyncAction;
use App\Enums\GenieType;
use App\Models\File;
use App\Models\Run;
use App\Models\RunResponse;
use App\Models\Vector;

interface GenieDataContract
{
    public function getType(): GenieType;

    public function getAction(): GenieSyncAction;

    public function getModel(): File|Vector|Run|RunResponse;

    public function getModelProviderId(): ?string;

    public function getProviderIdField(): string;

    public function getData(): array;

    public function getRequest(): array;

    public function setResponse(array $response): void;

    public function getResponse(): array;

    public function getResponseProviderId(): ?string;

    public function nextAction(): ?GenieSyncAction;
}
