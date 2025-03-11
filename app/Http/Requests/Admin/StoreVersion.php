<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\VersionStatus;
use App\Models\Version;

class StoreVersion extends FormRequest
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
     * @return Version
     */
    public function handle(): Version
    {

        return Version::create([
            'name' => $this->input('name'),
            'description' => $this->input('description'),
            'status' => $this->input('status'),
            'is_default' => $this->input('is_default', 0),
        ]);

    }
}
