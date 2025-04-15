<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\DeleteAssistant;
use App\Builders\AssistantQuery;
use App\Enums\AssistantType;
use App\Http\Requests\Admin\StoreAssistant;
use App\Http\Requests\Admin\UpdateAssistant;
use App\Http\Resources\Admin\AssistantResource;
use App\Models\AIModel;
use App\Models\Assistant;
use App\Models\Vector;
use App\Support\Facades\OpenAI;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use PhpParser\Node\Stmt\Foreach_;

class AssistantsController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection|Response
    {

        $records = AssistantQuery::apply($request)
            ->latest()
            ->paginate(20)
            ->onEachSide(1)
            ->withQueryString();

        return Inertia::render('Genie/Admin/Assistants/Index', [
            'filter' => [
                'assistant_type' => $request->query('assistant_type', ''),
            ],
            'assistantTypes' => AssistantType::withTitle(),
            'records' => AssistantResource::collection($records),
        ]);
    }

    public function create(Request $request): Response
    {

        return Inertia::render('Genie/Admin/Assistants/CreateEdit', [
            'mode' => 'create',
            'assistantTypes' => AssistantType::withTitle(),
            'assistantType' => $request->input('assistant_type'),
            'models' => AIModel::all(),
            'vectorIds' => Vector::all('id', 'name', 'vector_type'),
            'record' => null
        ]);
    }

    public function store(StoreAssistant $storeAssistant): RedirectResponse
    {
        $record = $storeAssistant->handle();

        return redirect()
            ->route('genie.admin.assistants.edit', ['assistant' => $record->uuid])
            ->with('success', __('genie.created'));
    }

    public function edit(Request $request): Response
    {
        $record = Assistant::firstOrFailByUuid($request->route('assistant'));

        $vectors = Vector::all()->mapWithKeys(function ($item) {
            return [$item['id'] => $item['name']];
        });

        return Inertia::render('Genie/Admin/Assistants/CreateEdit', [
            'mode' => 'edit',
            'assistantTypes' => AssistantType::withTitle(),
            'models' => OpenAI::models()->list()->data,
            'vectorIds' => Vector::all('id', 'name', 'vector_type'),
            'record' => new AssistantResource($record)
        ]);
    }

    public function update(UpdateAssistant $updateAssistant): RedirectResponse
    {
        $record = $updateAssistant->handle();

        return redirect()
            ->route('genie.admin.assistants.edit', ['assistant' => $record->uuid])
            ->with('success', __('genie.updated'));
    }

    /**
     * @param DeleteAssistant $deleteAssistants
     * @return RedirectResponse
     */
    public function destroy(DeleteAssistant $deleteAssistants): RedirectResponse
    {
        $deleteAssistants->handle();

        return redirect()->route('genie.admin.assistants.index')
            ->with('success', __('genie.assistant_deleted'));
    }
}
