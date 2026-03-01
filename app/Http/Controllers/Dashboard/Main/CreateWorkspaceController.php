<?php

namespace App\Http\Controllers\Dashboard\Main;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Inovector\MixpostEnterprise\Http\Base\Requests\Dashboard\Main\StoreWorkspace;
use Inovector\MixpostEnterprise\Util;
use Inovector\MixpostEnterprise\Http\Base\Controllers\Dashboard\Main\CreateWorkspaceController as MixpostEnterpriseCreateWorkspaceController;


class CreateWorkspaceController extends MixpostEnterpriseCreateWorkspaceController
{
    public function create(): Response
    {
        return Inertia::render('Dashboard/Main/CreateWorkspace', [
            'locales' => Util::config('locales')
        ]);
    }
}
