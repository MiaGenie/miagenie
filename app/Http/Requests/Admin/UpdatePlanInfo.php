<?php

namespace App\Http\Requests\Admin;

use App\Models\PlanInfo;
use Arr;
use Illuminate\Foundation\Http\FormRequest;
use Inovector\Mixpost\Util;

class UpdatePlanInfo extends FormRequest
{
    public function rules(): array
    {
        return [
            'plan_id' => ['required', 'integer'],
            'description' => ['nullable', 'string']
        ];
    }

    /**
     * @return PlanInfo
     */
    public function handle(): PlanInfo
    {
        $record = PlanInfo::where(['plan_id' => $this->route('plan_id')])->firstOrFail();
        $locale = $this->route('locale');

        $record->setTranslation('description', $locale, $this->input('description'));

        $record->save();

        return $record;
    }

}
