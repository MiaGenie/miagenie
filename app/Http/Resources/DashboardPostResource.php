<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Inovector\Mixpost\Facades\Settings;
use Inovector\Mixpost\Http\Base\Resources\AccountResource;
use Inovector\Mixpost\Http\Base\Resources\PostVersionResource;
use Inovector\Mixpost\Util;

class DashboardPostResource extends JsonResource
{
    public static $wrap = null;

    public function toArray($request): array
    {
        return [
            'id' => $this->uuid,
            'status' => $this->status(),
            'accounts' => AccountResource::collection($this->whenLoaded('accounts')),
            'versions' => DashboardPostVersionResource::collection($this->whenLoaded('versions')),
            'scheduled_at' => [
                'date' => $this->scheduled_at?->tz(Settings::get('timezone'))->toDateString(),
                'time' => $this->scheduled_at?->tz(Settings::get('timezone'))->format('H:i'),
                'human' => $this->scheduled_at?->tz(Settings::get('timezone'))->translatedFormat("D, M j, " . Util::timeFormat())
            ],
            'published_at' => [
                'human' => $this->published_at?->tz(Settings::get('timezone'))->translatedFormat("D, M j, " . Util::timeFormat())
            ],
        ];
    }

    protected function status()
    {
        if ($this->isScheduleProcessing()) {
            return 'publishing';
        }

        return strtolower($this->status->name);
    }
}
