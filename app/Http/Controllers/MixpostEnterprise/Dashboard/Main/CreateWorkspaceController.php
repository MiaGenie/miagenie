<?php

namespace App\Http\Controllers\MixpostEnterprise\Dashboard\Main;

use App\Http\Requests\MixpostEnterprise\Dashboard\Main\StoreWorkspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Inovector\MixpostEnterprise\Util;


class CreateWorkspaceController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('MixpostEnterprise/Dashboard/Main/CreateWorkspace', [
            'locales' => Util::config('locales')
        ]);
    }

    public function store(StoreWorkspace $storeWorkspace): RedirectResponse
    {
        $workspace = $storeWorkspace->handle();

        return redirect()->route('mixpost.dashboard', ['workspace' => $workspace->uuid]);
    }
}
