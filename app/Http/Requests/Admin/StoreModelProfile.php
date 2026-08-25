<?php

namespace App\Http\Requests\Admin;

use App\Concerns\Requests\ValidatesModelProfile;
use App\Models\ModelProfile;
use Illuminate\Foundation\Http\FormRequest;

class StoreModelProfile extends FormRequest
{
    use ValidatesModelProfile;

    public function rules(): array
    {
        return $this->modelProfileRules();
    }

    public function handle(): ModelProfile
    {
        return ModelProfile::create(
            $this->modelProfileAttributes() + [
                'position' => (int) ModelProfile::max('position') + 1,
            ]
        );
    }
}
