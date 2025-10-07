<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use App\Enums\VersionStatus;
use App\Http\Requests\Admin\StoreVersion;
use App\Http\Requests\Admin\UpdateVersion;
use App\Http\Resources\Admin\VersionResource;
use App\Models\Version;
use Inertia\Response;

class VersionsController extends Controller
{
    /**
     * @return Response
     */
    public function index()
    {
        $records = Version::query()
            ->latest()
            ->paginate(100)
            ->onEachSide(1)
            ->withQueryString();

        return Inertia::render('Genie/Admin/Versions/Index', [
            'records' => fn () => VersionResource::collection($records),
            'statusTypes' => VersionStatus::withTitle()
        ]);
    }

    /**
     * @return Response
     */
    public function create()
    {
        return Inertia::render('Genie/Admin/Versions/CreateEdit', [
            'mode' => 'create',
            'record' => null,
            'statusTypes' => VersionStatus::withTitle()
        ]);
    }

    /**
     * @param StoreVersion $storeVersion
     * @return RedirectResponse
     */
    public function store(StoreVersion $storeVersion)
    {
        $record = $storeVersion->handle();

        return redirect()
            ->route('genie.admin.versions.edit', ['version' => $record->uuid])
            ->with('success', __('genie.version_created'));
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function edit(Request $request)
    {
        $record = Version::firstOrFailByUuid($request->route('version'));

        return Inertia::render('Genie/Admin/Versions/CreateEdit', [
            'mode' => 'edit',
            'record' => new VersionResource($record),
            'statusTypes' => VersionStatus::withTitle()
        ]);
    }

    /**
     * @param UpdateVersion $updateVersion
     * @return RedirectResponse
     */
    public function update(UpdateVersion $updateVersion)
    {
        $updateVersion->handle();

        return redirect()->back()->with('success', __('genie.version_updated'));
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function destroy(Request $request)
    {
        $query = Version::where('uuid', $request->route('version'))->delete();

        if (!$query) {
            return redirect()
                ->route('genie.admin.versions.index')
                ->with('error', __('genie.version_not_found'));
        }

        return redirect()->route('genie.admin.versions.index')
            ->with('success', __('genie.version_deleted'));
    }
}
