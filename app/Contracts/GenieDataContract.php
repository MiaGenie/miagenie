<?php

namespace App\Contracts;

interface GenieDataContract
{
    /**
     * @return string
     */
    public function getType(): string;

    /**
     * @return string
     */
    public function getAction(): string;

    /**
     * @return \App\Models\File|\App\Models\Vector|\App\Models\Assistant|\App\Models\Thread
     */
    public function getModel(): \App\Models\File | \App\Models\Vector | \App\Models\Assistant | \App\Models\Thread;

    /**
     * @return string
     */
    public function getModelProviderId(): string;

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
    public function nextAction(): string;

}
