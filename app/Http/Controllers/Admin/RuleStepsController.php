<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RuleSubType;
use App\Enums\RuleType;
use App\Enums\VersionGroupType;
use App\Enums\VersionStatus;
use App\Http\Requests\Admin\UpdateRuleStepTranslations;
use App\Http\Resources\Admin\RuleStepTranslationResource;
use App\Http\Resources\Admin\VersionResource;
use App\Models\AIModel;
use App\Models\Idea;
use App\Models\Rule;
use App\Models\RuleStep;
use App\Models\Vector;
use App\Models\Version;
use App\Models\VersionField;
use Arr;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use App\Enums\RuleStatus;
use App\Http\Requests\Admin\StoreRuleStep;
use App\Http\Requests\Admin\UpdateRuleStep;
use App\Http\Requests\Admin\UpdateRuleStepPositions;
use App\Http\Resources\Admin\RuleStepResource;
use App\Http\Resources\Admin\RuleResource;
use Inertia\Response;
use Inovector\Mixpost\Util;

class RuleStepsController extends Controller
{


    /**
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        $rule = Rule::firstOrFailByUuid($request->route('rule'));
        $version = Version::firstOrFailByUuid($request->route('version'));

        $records = RuleStep::query()
            ->where('rule_id', $rule->id)
            ->oldest('position')
            ->paginate(100)
            ->onEachSide(1);

        return Inertia::render('Genie/Admin/Versions/Rules/Steps/Index', [
            'rule' => new RuleResource($rule),
            'ruleTypes' => RuleType::withTitle(),
            'ruleSubTypes' => RuleSubType::withTitle(),
            'version' => new VersionResource($version),
            'records' => RuleStepResource::collection($records),
            'versionStatusTypes' => VersionStatus::withTitle(),
            'ruleStatusTypes' => RuleStatus::withTitle()
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function indexTranslate(Request $request)
    {
        $rule = Rule::firstOrFailByUuid($request->route('rule'));
        $version = Version::firstOrFailByUuid($request->route('version'));

        $records = RuleStep::query()
            ->where('rule_id', $rule->id)
            ->oldest('position')
            ->paginate(100)
            ->onEachSide(1);

        $translations = $this->getTranslations($records->collect());

        return Inertia::render('Genie/Admin/Versions/Rules/Steps/IndexTranslate', [
            'rule' => new RuleResource($rule),
            'ruleTypes' => RuleType::withTitle(),
            'ruleSubTypes' => RuleSubType::withTitle(),
            'version' => new VersionResource($version),
            'records' => RuleStepResource::collection($records),
            'versionStatusTypes' => VersionStatus::withTitle(),
            'ruleStatusTypes' => RuleStatus::withTitle(),
            'translations' => $translations,
            'locales' => Util::config('locales')
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function create(Request $request)
    {
        $rule = Rule::firstOrFailByUuid($request->route('rule'));
        $version = Version::firstOrFailByUuid($request->route('version'));

        $outputFields = VersionField::where(
            [
                'version_id' => $rule->version_id,
                'group_type' => VersionGroupType::STRATEGIES,
            ]
        )->with('options')->get();

        return Inertia::render('Genie/Admin/Versions/Rules/Steps/CreateEdit', [
            'mode' => 'create',
            'rule' => new RuleResource($rule),
            'ruleSubTypes' => RuleSubType::withTitle('', $rule->rule_type->value),
            'record' => null,
            'version' => new VersionResource($version),
            'models' => AIModel::all(),
            'vectorIds' => Vector::all('id', 'name', 'vector_type'),
            'outputFields' => $outputFields,
            'outputIdeaFields' => [0 => 'theme', 1 => 'description'],
            'versionStatusTypes' => VersionStatus::withTitle(),
            'ruleStatusTypes' => RuleStatus::withTitle()
        ]);
    }

    /**
     * @param StoreRuleStep $storeRuleStep
     * @return RedirectResponse
     * @throws \Throwable
     */
    public function store(StoreRuleStep $storeRuleStep)
    {
        $rule = Rule::firstOrFailByUuid($storeRuleStep->route('rule'));
        $version = Version::firstOrFailByUuid($storeRuleStep->route('version'));

        $record = $storeRuleStep->handle();

        return redirect()
            ->route('genie.admin.versions.rules.steps.edit', [
                'version' => $version->uuid,
                'rule' => $rule->uuid,
                'step' => $record->uuid,
            ])
            ->with('success', __('genie.step_created'));
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function edit(Request $request)
    {
        $rule = Rule::firstOrFailByUuid($request->route('rule'));
        $version = Version::firstOrFailByUuid($request->route('version'));

        $record = RuleStep::firstOrFailByUuid($request->route('step'));

        $outputFields = VersionField::where(
            [
                'version_id' => $rule->version_id,
                'group_type' => VersionGroupType::STRATEGIES,
            ]
        )->with('options')->get();

        return Inertia::render('Genie/Admin/Versions/Rules/Steps/CreateEdit', [
            'mode' => 'edit',
            'rule' => new RuleResource($rule),
            'ruleSubTypes' => RuleSubType::withTitle(),
            'record' => new RuleStepResource($record),
            'version' => new VersionResource($version),
            'models' => AIModel::all(),
            'vectorIds' => Vector::all('id', 'name', 'vector_type'),
            'outputFields' => $outputFields,
            'outputIdeaFields' => [0 => 'theme', 1 => 'description'],
            'versionStatusTypes' => VersionStatus::withTitle(),
            'ruleStatusTypes' => RuleStatus::withTitle()
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function translate(Request $request)
    {
        $rule = Rule::firstOrFailByUuid($request->route('rule'));
        $version = Version::firstOrFailByUuid($request->route('version'));

        $record = RuleStep::firstOrFailByUuid($request->route('step'))->setLocale($request->route('locale'));

        $locales = Util::config('locales');
        $locale = Arr::first($locales, function ($value) use ($request) {
            return $value['long'] === $request->route('locale');
        });

        return Inertia::render('Genie/Admin/Versions/Rules/Steps/Translate', [
            'rule' => new RuleResource($rule),
            'record' => new RuleStepTranslationResource($record),
            'version' => new VersionResource($version),
            'locale' => $locale
        ]);
    }

    /**
     * @param UpdateRuleStep $updateRuleStep
     * @return RedirectResponse
     * @throws \Throwable
     */
    public function update(UpdateRuleStep $updateRuleStep)
    {
        $updateRuleStep->handle();

        return redirect()->back()->with('success', __('genie.step_updated'));
    }

    /**
     * @param UpdateRuleStepTranslations $updateRuleStepTranslations
     * @return RedirectResponse
     * @throws \Throwable
     */
    public function updateTranslations(UpdateRuleStepTranslations $updateRuleStepTranslations)
    {
        $updateRuleStepTranslations->handle();

        return redirect()->back()->with('success', __('genie.step_updated'));
    }

    /**
     * @param UpdateRuleStepPositions $updateRuleStepPositions
     * @return JsonResponse
     */
    public function updatePositions(UpdateRuleStepPositions $updateRuleStepPositions)
    {
        $updateRuleStepPositions->handle();

        return response()->json(['message' => __('genie.step_positions_updated')]);
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function destroy(Request $request)
    {
        $result = RuleStep::firstOrFailByUuid($request->route('step'))->delete();

        $rule = Rule::firstOrFailByUuid($request->route('rule'));
        $version = Version::firstOrFailByUuid($request->route('version'));

        if (!$result) {
            return redirect()
                ->route('genie.admin.versions.rules.steps.index', [
                        'version' => $version->uuid,
                        'rule' => $rule->uuid,
                    ])
                ->with('error', __('genie.step_not_found'));
        }

        return redirect()->route('genie.admin.versions.rules.steps.index', [
                'version' => $version->uuid,
                'rule' => $rule->uuid
            ])
            ->with('success', __('genie.step_deleted'));
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
                $translations[$record->uuid][$field] = $locales;
            }
        }
        return $translations;
    }
}
