<?php

namespace App\Http\Requests\Admin;

use App\Concerns\Requests\ValidatesModelProfile;
use App\Models\ModelProfile;
use Illuminate\Foundation\Http\FormRequest;

class UpdateModelProfile extends FormRequest
{
    use ValidatesModelProfile;

    public function rules(): array
    {
        return $this->modelProfileRules();
    }

    public function handle(): bool
    {
        $record = ModelProfile::firstOrFailByUuid($this->route('model_profile'));

        return $record->update($this->modelProfileAttributes());
    }
}
