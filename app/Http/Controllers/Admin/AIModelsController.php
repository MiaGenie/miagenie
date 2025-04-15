<?php

namespace App\Http\Controllers\Admin;

use App\Http\Resources\Admin\AIModelResource;
use App\Models\AIModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use App\Http\Requests\Admin\StoreAIModel;
use App\Http\Requests\Admin\UpdateAIModel;
use Inertia\Response;

class AIModelsController extends Controller
{
    /**
     * @return Response
     */
    public function index()
    {
        $records = AIModel::query()
            ->oldest('model')
            ->paginate(50)
            ->onEachSide(1)
            ->withQueryString();

        return Inertia::render('Genie/Admin/AIModels/Index', [
            'records' => fn () => AIModelResource::collection($records)
        ]);
    }

    /**
     * @return Response
     */
    public function create()
    {
        return Inertia::render('Genie/Admin/AIModels/CreateEdit', [
            'mode' => 'create',
            'record' => null
        ]);
    }

    /**
     * @param StoreAIModel $storeAIModel
     * @return RedirectResponse
     */
    public function store(StoreAIModel $storeAIModel)
    {
        $record = $storeAIModel->handle();

        return redirect()
            ->route('genie.admin.ai_models.edit', ['ai_model' => $record->uuid])
            ->with('success', __('genie.ai_model_created'));
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function edit(Request $request)
    {
        $record = AIModel::firstOrFailByUuid($request->route('ai_model'));

        return Inertia::render('Genie/Admin/AIModels/CreateEdit', [
            'mode' => 'edit',
            'record' => new AIModelResource($record)
        ]);
    }

    /**
     * @param UpdateAIModel $updateAIModel
     * @return RedirectResponse
     */
    public function update(UpdateAIModel $updateAIModel)
    {
        $updateAIModel->handle();

        return redirect()->back()->with('success', __('genie.ai_model_updated'));
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function destroy(Request $request)
    {
        $query = AIModel::where('uuid', $request->route('ai_model'))->delete();

        if (!$query) {
            return redirect()
                ->route('genie.admin.ai_models.index')
                ->with('error', __('genie.ai_model_not_found'));
        }

        return redirect()->route('genie.admin.ai_models.index')
            ->with('success', __('genie.ai_model_deleted'));
    }
}
