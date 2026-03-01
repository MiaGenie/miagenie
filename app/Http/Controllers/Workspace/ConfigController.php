<?php

namespace App\Http\Controllers\Workspace;

use App\Http\Resources\BriefingResource;
use App\Http\Resources\CompetitorResource;
use App\Models\Briefing;
use App\Models\Competitor;
use App\Models\WorkspaceVersion;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Inovector\Mixpost\Facades\WorkspaceManager;

class ConfigController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $competitors = Competitor::query()
            ->latest()
            ->paginate(100)
            ->onEachSide(1)
            ->withQueryString();

        $competitorsFieldList = WorkspaceVersion::where('workspace_id', WorkspaceManager::current()->id)
            ->with(['version' => ['competitors']])
            ->firstOrFail()
            ->version
            ->competitors
            ->toArray();

        $briefing = Briefing::latest()
            ->first();

        $briefingsFieldList = WorkspaceVersion::where('workspace_id', WorkspaceManager::current()->id)
            ->with(['version' => ['briefings']])
            ->firstOrFail()
            ->version
            ->briefings
            ->toArray();

        return Inertia::render('Genie/Workspace/Config', [
            'competitors' => fn () => CompetitorResource::collection($competitors),
            'competitorsFieldList' => $competitorsFieldList,
            'briefing' => $briefing ? New BriefingResource($briefing) : null,
            'briefingsFieldList' => $briefingsFieldList
        ]);
    }
}
