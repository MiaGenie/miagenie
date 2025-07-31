<?php

namespace App\Http\Controllers\Admin;
use App\Enums\GenieSyncStatus;
use App\Enums\GenieType;
use App\Http\Resources\Admin\LogResource;
use App\Models\Log;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

class LogsController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection|Response
    {
        $logRecords = Log::query()
            ->latest()
            ->paginate(100)
            ->onEachSide(1)
            ->withQueryString();

        return Inertia::render('Genie/Admin/Logs/Index', [
            'records' => LogResource::collection($logRecords),
            'genieTypes' => GenieType::withTitle(),
            'genieSyncStatus' => GenieSyncStatus::withTitle(),
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
