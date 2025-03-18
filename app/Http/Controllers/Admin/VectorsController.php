<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VectorType;
use App\Http\Requests\Admin\StoreVector;
use App\Http\Requests\Admin\UpdateVector;
use App\Http\Resources\Admin\VectorResource;
use App\Models\File;
use App\Models\Vector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

class VectorsController extends Controller
{

    /**
     * @param Request $request
     * @return AnonymousResourceCollection|Response
     */
    public function index(Request $request): AnonymousResourceCollection|Response
    {

        $records = Vector::query()
            ->latest()
            ->paginate(20)
            ->onEachSide(1)
            ->withQueryString();

        return Inertia::render('Genie/Admin/Vectors/Index', [
            'records' => fn () => VectorResource::collection($records),
        ]);
    }

    /**
     * @return Response
     */
    public function create(): Response
    {

        return Inertia::render('Genie/Admin/Vectors/CreateEdit', [
            'mode' => 'create',
            'mimeTypes' => File::mimeTypes(),
            'vectorTypes' => VectorType::withTitle(),
            'record' => null,
        ]);
    }

    /**
     * @param StoreVector $storeVector
     * @return RedirectResponse
     */
    public function store(StoreVector $storeVector): RedirectResponse
    {
        $record = $storeVector->handle();

        return redirect()
            ->route('genie.admin.vectors.edit', ['vectors' => $record->uuid])
            ->with('success', __('genie.created'));
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function edit(Request $request): Response
    {
        $record = Vector::firstOrFailByUuid($request->route('vector'));

        return Inertia::render('Genie/Admin/Vectors/CreateEdit', [
            'mode' => 'edit',
            'mimeTypes' => File::mimeTypes(),
            'vectorTypes' => VectorType::withTitle(),
            'record' => new VectorResource($record),
        ]);
    }

    /**
     * @param UpdateVector $updateVector
     * @return RedirectResponse
     */
    public function update(UpdateVector $updateVector): RedirectResponse
    {
        $updateVector->handle();

        return redirect()->back()->with('success', __('genie.updated'));
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function destroy(Request $request): RedirectResponse
    {
        $query = Vector::where('uuid', $request->route('vector'))->delete();

        if (! $query) {
            return redirect()
                ->route('genie.admin.vectors.index')
                ->with('error', __('genie.vectors_not_found'));
        }

        return redirect()->route('genie.admin.vectors.index')
            ->with('success', __('genie.vectors_deleted'));
    }
}
