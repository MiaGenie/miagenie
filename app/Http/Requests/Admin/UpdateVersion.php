<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\VersionStatus;
use App\Models\Version;

class UpdateVersion extends FormRequest
{
    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::enum(VersionStatus::class)]
        ];
    }

    /**
     * @return int
     */
    public function handle(): int
    {
        $record = Version::firstOrFailByUuid($this->route('version'));

        return $record->update([
            'name' => $this->input('name'),
            'description' => $this->input('description'),
            'status' => $this->input('status'),
            'is_default' => $this->input('is_default'),
        ]);
    }
}
