<?php

namespace App\Http\Controllers\Admin;

use App\Builders\RuleQuery;
use App\Enums\RuleStatus;
use App\Enums\RuleSubType;
use App\Enums\RuleType;
use App\Enums\VersionStatus;
use App\Http\Requests\Admin\CloneRule;
use App\Http\Requests\Admin\CloneRuleInto;
use App\Http\Requests\Admin\StoreRule;
use App\Http\Requests\Admin\UpdateRule;
use App\Http\Resources\Admin\RuleResource;
use App\Http\Resources\Admin\VersionResource;
use App\Models\Rule;
use App\Models\Version;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

class RulesController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection|Response
    {

        $version = Version::firstOrFailByUuid($request->route('version'));

        $records = RuleQuery::apply($request)
            ->where('version_id', $version->id)
            ->latest()
            ->paginate(100)
            ->onEachSide(1)
            ->withQueryString();

        return Inertia::render('Genie/Admin/Versions/Rules/Index', [
            'filter' => [
                'rule_type' => $request->query('rule_type', ''),
            ],
            'ruleTypes' => RuleType::withTitle(),
            'statusTypes' => RuleStatus::withTitle(),
            'records' => RuleResource::collection($records),
            'version' => new VersionResource($version),
        ]);
    }

    public function create(Request $request): Response
    {
        $version = Version::firstOrFailByUuid($request->route('version'));
        $versions = Version::whereNotIn('status', [VersionStatus::ARCHIVED])
            ->whereNot('id', $version->id)->get();

        return Inertia::render('Genie/Admin/Versions/Rules/CreateEdit', [
            'mode' => 'create',
            'ruleTypes' => RuleType::withTitle(),
            'type' => $request->input('rule_type'),
            'version' => new VersionResource($version),
            'versions' => VersionResource::collection($versions),
            'statusTypes' => RuleStatus::withTitle(),
            'record' => null
        ]);
    }

    public function store(StoreRule $storeRule): RedirectResponse
    {
        $record = $storeRule->handle();
        $version = Version::firstOrFailByUuid($storeRule->route('version'));

        return redirect()
            ->route('genie.admin.versions.rules.edit', [
                'version' => $version->uuid,
                'rule' => $record->uuid
            ])
            ->with('success', __('genie.created'));
    }

    public function edit(Request $request): Response
    {
        $record = Rule::firstOrFailByUuid($request->route('rule'));

        $version = Version::firstOrFailByUuid($request->route('version'));
        $versions = Version::whereNotIn('status', [VersionStatus::ARCHIVED])
            ->whereNot('id', $version->id)->get();

        return Inertia::render('Genie/Admin/Versions/Rules/CreateEdit', [
            'mode' => 'edit',
            'ruleTypes' => RuleType::withTitle(),
            'statusTypes' => RuleStatus::withTitle(),
            'version' => new VersionResource($version),
            'versions' => VersionResource::collection($versions),
            'record' => new RuleResource($record)
        ]);
    }

    public function update(UpdateRule $updateRule): RedirectResponse
    {
        $updateRule->handle();

        return redirect()->back()->with('success', __('genie.updated'));
    }

    /**
     * @param CloneRule $cloneRule
     * @return RedirectResponse
     */
    public function clone(CloneRule $cloneRule)
    {
        $record = $cloneRule->handle();

        $version = Version::firstOrFailByUuid($cloneRule->route('version'));

        return redirect()
            ->route('genie.admin.versions.rules.index', [
                'version' => $version->uuid
            ])
            ->with('success', __('genie.rule_cloned'));

    }


    /**
     * @param CloneRuleInto $cloneRuleInto
     * @return RedirectResponse
     */
    public function cloneInto(CloneRuleInto $cloneRuleInto)
    {
        $record = $cloneRuleInto->handle();

        $version = Version::firstOrFailByUuid($cloneRuleInto->route('target'));

        return redirect()
            ->route('genie.admin.versions.rules.index', [
                'version' => $version->uuid
            ])
            ->with('success', __('genie.rule_cloned'));

    }

    public function destroy(Request $request): RedirectResponse
    {
        $result = Rule::where('uuid', $request->route('rule'))->delete();
        $version = Version::firstOrFailByUuid($request->route('version'));

        if (!$result) {
            return redirect()
                ->route('genie.admin.versions.rules.index', [
                    'version' => $version->uuid
            ])
                ->with('error', __('genie.not_found'));
        }

        return redirect()->route('genie.admin.versions.rules.index', [
            'version' => $version->uuid
        ])
            ->with('success', __('genie.rule_deleted'));
    }
}
