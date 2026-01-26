<?php

namespace App\Http\Resources;

use App\Enums\RunStatus;
use App\Http\Resources\Admin\RunResource;
use Illuminate\Http\Resources\Json\JsonResource;

class StrategyResource extends JsonResource
{
    /**
     * @var string|null
     */
    public static $wrap = null;

    /**
     * @param $request
     * @return array
     */
    public function toArray($request): array
    {

        return [
            'id' => $this->uuid,
            'name' => $this->name,
            'active' => $this->active,
            'content' => $this->content,
            'created_at' => $this->created_at,
            'status' => $this->status,
        ];

    }
}
