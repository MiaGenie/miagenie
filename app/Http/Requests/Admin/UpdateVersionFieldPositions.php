<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\VersionField;

class UpdateVersionFieldPositions extends FormRequest
{
    /**
     * @return array[]
     */
    public function rules(): array
    {
        return [
            'positions.*.position' => ['required', 'distinct']
        ];
    }

    /**
     * @return void
     */
    public function handle(): void
    {
        foreach ($this->input('positions') as $item) {
            VersionField::firstOrFailByUuid($item['id'])->update(['position' => $item['position']]);
        }
    }
}
