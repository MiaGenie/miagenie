<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\UpdateVersionFieldOptionTranslations;
use App\Http\Resources\Admin\VersionFieldOptionTranslationResource;
use Arr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use App\Enums\VersionStatus;
use App\Http\Requests\Admin\UpdateVersionField;
use App\Http\Resources\Admin\VersionResource;
use App\Models\Version;
use App\Models\VersionField;
use Inertia\Response;
use Inovector\Mixpost\Util;

class VersionFieldOptionsController extends Controller
{

    /**
     * @param Request $request
     * @return Response
     */
    public function translate(Request $request)
    {
        $version = Version::firstOrFailByUuid($request->route('version'));

        $record = VersionField::with('options')
            ->where('uuid', $request->route('field'))
            ->firstOrFail();

        $locales = Util::config('locales');
        $locale = Arr::first($locales, function ($value) use ($request) {
            return $value['long'] === $request->route('locale');
        });

        return Inertia::render('Genie/Admin/Versions/Fields/TranslateOptions', [
            'version' => new VersionResource($version),
            'field' => $record,
            'records' => VersionFieldOptionTranslationResource::collection($record->options),
            'groupType' => $request->input('group_type'),
            'statusTypes' => VersionStatus::withTitle(),
            'locale' => $locale
        ]);
    }

    /**
     * @param UpdateVersionFieldOptionTranslations $updateOptionTranslations
     * @return RedirectResponse
     * @throws \Throwable
     */
    public function updateTranslations(UpdateVersionFieldOptionTranslations $updateOptionTranslations)
    {
        $updateOptionTranslations->handle();

        return redirect()->back()->with('success', __('genie.field_updated'));
    }
}
