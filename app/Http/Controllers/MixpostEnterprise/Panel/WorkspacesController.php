<?php

namespace App\Http\Controllers\MixpostEnterprise\Panel;

use App\Enums\VersionStatus;
use App\Http\Requests\MixpostEnterprise\Panel\Workspace\StoreWorkspace;
use App\Http\Requests\MixpostEnterprise\Panel\Workspace\UpdateWorkspace;
use App\Http\Resources\MixpostEnterprise\WorkspaceResource;
use App\Models\Version;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Inovector\MixpostEnterprise\Configs\BillingConfig;
use Inovector\MixpostEnterprise\Http\Base\Resources\PlanResource;
use Inovector\MixpostEnterprise\Http\Base\Resources\SubscriptionResource;
use Inovector\MixpostEnterprise\Models\Plan;
use Inovector\MixpostEnterprise\Util;

class WorkspacesController extends Controller
{

    public function create(): Response
    {
        $versions = Version::whereIn('status', [VersionStatus::ENABLED, VersionStatus::TESTING])->get(['id', 'name']);

        return Inertia::render('MixpostEnterprise/Panel/Workspaces/CreateEdit', [
            'mode' => 'create',
            'locales' => Util::config('locales'),
            'versions' => $versions
        ]);
    }

    public function edit(Request $request): Response
    {
        $workspace = Workspace::firstOrFailByUuid($request->route('workspace'))
            ->load('owner');

        $versions = Version::whereIn('status', [VersionStatus::ENABLED, VersionStatus::TESTING])->get(['id', 'name']);

        return Inertia::render('MixpostEnterprise/Panel/Workspaces/CreateEdit', [
            'mode' => 'edit',
            'workspace' => new WorkspaceResource($workspace),
            'locales' => Util::config('locales'),
            'versions' => $versions
        ]);
    }

    public function store(StoreWorkspace $storeWorkspace): RedirectResponse
    {
        $workspace = $storeWorkspace->handle();

        return redirect()
            ->route('mixpost_e.workspaces.view', ['workspace' => $workspace->uuid]);
    }

    public function view(Request $request): Response
    {
        $workspace = Workspace::firstOrFailByUuid($request->route('workspace'))
            ->load(['owner', 'users', 'genericSubscriptionPlan']);

        $subscription = $workspace->subscription();

        $subscription?->load(['planMonthly', 'planYearly']);

        return Inertia::render('MixpostEnterprise/Panel/Workspaces/View', [
            'workspace' => (new WorkspaceResource($workspace))->additionalFields([
                'payment_method' => [
                    'type' => $workspace->pm_type,
                    'card_brand' => $workspace->pm_card_brand,
                    'card_last_four' => $workspace->pm_card_last_four,
                    'card_expires' => $workspace->pm_card_expires,
                ]
            ]),
            'subscription' => $subscription ? new SubscriptionResource($subscription) : null,
            'billing_configs' => (new BillingConfig())->all(),
            'plans' => PlanResource::collection(Plan::get())->resolve(),
        ]);
    }

    public function update(UpdateWorkspace $updateWorkspace): RedirectResponse
    {
        $updateWorkspace->handle();

        return redirect()
            ->back()
            ->with('success', __('mixpost-enterprise::workspace.workspace_updated'));
    }
}
