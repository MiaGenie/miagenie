<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\StorePlanInfo;
use App\Http\Resources\Admin\PlanInfoResource;
use App\Models\PlanInfo;
use Arr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;
use Illuminate\Support\Collection;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inovector\Mixpost\Util;
use Inovector\MixpostEnterprise\Http\Base\Resources\PlanResource;
use Inovector\MixpostEnterprise\Models\Plan;

class PlanInfoController extends Controller
{
    public function index()
    {
        $records = Plan::query()
            ->paginate(100)
            ->onEachSide(1);

        $recordsTranslations = PlanInfo::whereIn('id', $records->pluck('id'));
        $translations = $this->getTranslations($recordsTranslations->get());

        return Inertia::render('Genie/Admin/Plans/Index', [
            'records' => PlanResource::collection($records),
            'translations' => $translations,
            'locales' => Util::config('locales')
        ]);

    }

    /**
     * @param Request $request
     * @return Response
     */
    public function edit(Request $request)
    {

        $record = PlanInfo::find($request->route('plan'))->firstOrCreate()->setLocale($request->route('locale'));

        $locales = Util::config('locales');
        $locale = Arr::first($locales, function ($value) use ($request) {
            return $value['long'] === $request->route('locale');
        });

        return Inertia::render('Genie/Admin/Plans/CreateEdit', [
            'record' => new PlanInfoResource($record),
            'locale' => $locale
        ]);
    }

    /**
     * @param StorePlanInfo $storePlanInfo
     * @return RedirectResponse
     * @throws \Throwable
     */
    public function store(StorePlanInfo $storePlanInfo)
    {
        $rule = PlanInfo::firstOrFailByUuid($storePlanInfo->route('plan'));

        $record = $storePlanInfo->handle();

        return redirect()
            ->route('genie.admin.versions.rules.steps.edit', [
                'rule' => $rule->uuid,
                'step' => $record->uuid,
            ])
            ->with('success', __('genie.step_created'));
    }

    /**
     * @param Collection $records
     * @return array
     */
    public function getTranslations(Collection $records): array
    {
        $translations = [];

        foreach ($records as $record) {
            $recordTranslations = $record->getTranslations();
            foreach ($recordTranslations as $field => $locales) {
                array_walk_recursive($locales, function (&$item) {
                    $item = !empty($item);
                });
                $translations[$record->plan_id][$field] = $locales;
            }
        }
        return $translations;
    }
}
