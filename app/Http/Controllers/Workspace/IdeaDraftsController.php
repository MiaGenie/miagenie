<?php

namespace App\Http\Controllers\Workspace;

use App\Http\Requests\Workspace\Draft\IdeaDrafts;
use App\Http\Resources\DraftResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class IdeaDraftsController extends Controller
{
    public function __invoke(IdeaDrafts $ideaDrafts): JsonResponse
    {
        return response()->json(DraftResource::collection($ideaDrafts->handle()));
    }
}
