<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RuleStatus;
use App\Enums\RuleType;
use App\Http\Resources\Admin\ThreadResource;
use App\Models\Rule;
use App\Models\Thread;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

class ThreadsController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection|Response
    {
        $threadsRecords = Thread::query()
            ->latest()
            ->paginate(100)
            ->onEachSide(1)
            ->withQueryString();

        $rulesRecords = Rule::first();

        return Inertia::render('Genie/Admin/Threads/Index', [
            'filter' => [
                'rule_type' => $request->query('rule_type', ''),
            ],
            'ruleTypes' => RuleType::withTitle(),
            'statusTypes' => RuleStatus::withTitle(),
            'records' => ThreadResource::collection($threadsRecords),
        ]);
    }

    public function view(Request $request): Response
    {
        $threads = Thread::firstOrFailByUuid($request->route('thread'));

        return Inertia::render('Genie/Admin/Threads/View', [
            'mode' => 'edit',
            'ruleTypes' => RuleType::withTitle(),
            'record' => new ThreadResource($threads),
        ]);
    }
}
