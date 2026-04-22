<?php

namespace App\Http\Requests\Admin;

use App\Models\PlanInfo;
use Illuminate\Foundation\Http\FormRequest;

class StorePlanInfo extends FormRequest
{
    public function rules(): array
    {
/*        return [
            'plan_id' => ['required', 'integer'],
            'description' => ['nullable'],
        ];*/
    }

    /**
     * @return PlanInfo
     */
    public function handle(): PlanInfo
    {

        return PlanInfo::create([
            'plan_id' => $this->input('plan'),
            'description' => $this->input('description'),
        ]);
    }

}
