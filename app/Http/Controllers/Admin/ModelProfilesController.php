<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\Requests\ValidatesModelProfile;
use App\Enums\ModelTier;
use App\Http\Requests\Admin\StoreModelProfile;
use App\Http\Requests\Admin\UpdateModelProfile;
use App\Http\Resources\Admin\ModelProfileResource;
use App\Models\ModelProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

class ModelProfilesController extends Controller
{
    /**
     * @return Response
     */
    public function index()
    {
        $records = ModelProfile::query()
            ->oldest('position')
            ->paginate(50)
            ->onEachSide(1)
            ->withQueryString();

        return Inertia::render('Genie/Admin/ModelProfiles/Index', [
            'records' => fn () => ModelProfileResource::collection($records),
        ]);
    }

    /**
     * @return Response
     */
    public function create()
    {
        return Inertia::render('Genie/Admin/ModelProfiles/CreateEdit', [
            'mode' => 'create',
            'record' => null,
            'providers' => ValidatesModelProfile::providerOptions(),
            'modelTiers' => ModelTier::withTitle()->values(),
        ]);
    }

    /**
     * @return RedirectResponse
     */
    public function store(StoreModelProfile $storeModelProfile)
    {
        $record = $storeModelProfile->handle();

        return redirect()
            ->route('genie.admin.model_profiles.edit', ['model_profile' => $record->uuid])
            ->with('success', __('genie.model_profile_created'));
    }

    /**
     * @return Response
     */
    public function edit(Request $request)
    {
        $record = ModelProfile::firstOrFailByUuid($request->route('model_profile'));

        return Inertia::render('Genie/Admin/ModelProfiles/CreateEdit', [
            'mode' => 'edit',
            'record' => new ModelProfileResource($record),
            'providers' => ValidatesModelProfile::providerOptions(),
            'modelTiers' => ModelTier::withTitle()->values(),
        ]);
    }

    /**
     * @return RedirectResponse
     */
    public function update(UpdateModelProfile $updateModelProfile)
    {
        $updateModelProfile->handle();

        return redirect()->back()->with('success', __('genie.model_profile_updated'));
    }

    /**
     * @return RedirectResponse
     */
    public function destroy(Request $request)
    {
        $record = ModelProfile::findByUuid($request->route('model_profile'));

        if (! $record) {
            return redirect()
                ->route('genie.admin.model_profiles.index')
                ->with('error', __('genie.model_profile_not_found'));
        }

        if ($record->steps()->exists()) {
            return redirect()
                ->route('genie.admin.model_profiles.index')
                ->with('error', __('genie.model_profile_in_use'));
        }

        $record->delete();

        return redirect()->route('genie.admin.model_profiles.index')
            ->with('success', __('genie.model_profile_deleted'));
    }
}
