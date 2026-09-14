<?php

namespace App\Http\Controllers\Admin;

use App\Enums\GenieSyncStatus;
use App\Enums\GenieType;
use App\Http\Resources\Admin\LogResource;
use App\Models\AiRun;
use App\Models\AiRunStep;
use App\Models\Log;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

class LogsController extends Controller
{
    /**
     * Every log entry, newest first; `?run_step={uuid}` narrows it to the calls of one run step.
     */
    public function index(Request $request): AnonymousResourceCollection|Response
    {
        $runStep = $request->query('run_step')
            ? AiRunStep::where('uuid', $request->query('run_step'))->first()
            : null;

        $logRecords = Log::query()
            ->when($runStep, fn ($query) => $query->where('run_step_id', $runStep->id))
            ->latest()
            ->paginate(100)
            ->onEachSide(1)
            ->withQueryString();

        return Inertia::render('Genie/Admin/Logs/Index', [
            'records' => LogResource::collection($logRecords),
            'genieTypes' => GenieType::withTitle(),
            'genieSyncStatus' => GenieSyncStatus::withTitle(),
            'filter' => ['run_step' => $request->query('run_step', '')],
            'runStep' => $runStep ? [
                'id' => $runStep->uuid,
                // Without the workspace scope: the admin has no current workspace to scope by.
                'run' => AiRun::withoutWorkspace()->find($runStep->run_id)?->uuid,
                'position' => $runStep->position,
                'name' => $runStep->step?->name,
            ] : null,
        ]);
    }

    public function view(Request $request): Response
    {
        $log = Log::all()->find($request->route('log'));

        return Inertia::render('Genie/Admin/Logs/View', [
            'log' => new LogResource($log),
            'logType' => $log->type->name,
            'logAction' => $log->action->name,
        ]);
    }
}
