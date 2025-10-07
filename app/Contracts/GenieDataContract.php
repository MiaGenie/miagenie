<?php

namespace App\Contracts;

use App\Enums\GenieSyncAction;
use App\Enums\GenieType;

interface GenieDataContract
{
    /**
     * @return GenieType
     */
    public function getType(): GenieType;

    /**
     * @return GenieSyncAction
     */
    public function getAction(): GenieSyncAction;

    /**
     * @return \App\Models\File|\App\Models\Vector|\App\Models\Assistant|\App\Models\Run|\App\Models\RunResponse
     */
    public function getModel(): \App\Models\File | \App\Models\Vector | \App\Models\Assistant | \App\Models\Run | \App\Models\RunResponse;

    /**
     * @return ?string
     */
    public function getModelProviderId(): ?string;

    /**
     * @return string
     */
    public function getProviderIdField(): string;

    /**
     * @return array
     */
    public function getData(): array;

    /**
     * @return array
     */
    public function getRequest(): array;

    /**
     * @param array $response
     * @return void
     */
    public function setResponse(array $response): void;

    /**
     * @return array
     */
    public function getResponse(): array;

    /**
     * @return string
     */
    public function getResponseProviderId(): string;

    /**
     * @return ?GenieSyncAction
     */
    public function nextAction(): ?GenieSyncAction;

}
