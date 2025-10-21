<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\UpdateVersionFieldTranslations;
use Arr;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use App\Builders\VersionGroupQuery;
use App\Enums\FormFieldType;
use App\Enums\FormInputType;
use App\Enums\VersionGroupType;
use App\Enums\VersionStatus;
use App\Http\Requests\Admin\StoreVersionField;
use App\Http\Requests\Admin\UpdateVersionField;
use App\Http\Requests\Admin\UpdateVersionFieldPositions;
use App\Http\Resources\Admin\VersionFieldResource;
use App\Http\Resources\Admin\VersionResource;
use App\Models\Version;
use App\Models\VersionField;
use Inertia\Response;
use Inovector\Mixpost\Util;

class VersionFieldsController extends Controller
{
    /**
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        $version = Version::firstOrFailByUuid($request->route('version'));

        $records = VersionGroupQuery::apply($request)
            ->oldest('position')
            ->paginate(100)
            ->onEachSide(1)
            ->withQueryString();

        return Inertia::render('Genie/Admin/Versions/Fields/Index', [
            'filter' => [
                'group_type' => $request->query('group_type', ''),
            ],
            'version' => new VersionResource($version),
            'records' => VersionFieldResource::collection($records),
            'groupTypes' => VersionGroupType::withGroupOptions(),
            'fieldTypes' => FormFieldType::withFieldOptions(),
            'inputTypes' => FormInputType::withInputOptions(),
            'statusTypes' => VersionStatus::withTitle()
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function indexTranslate(Request $request)
    {
        $version = Version::firstOrFailByUuid($request->route('version'));

        $records = VersionGroupQuery::apply($request)
            ->oldest('position')
            ->paginate(100)
            ->onEachSide(1)
            ->withQueryString();

        $translations = $this->getTranslations($records->collect());

        return Inertia::render('Genie/Admin/Versions/Fields/IndexTranslate', [
            'filter' => [
                'group_type' => $request->query('group_type', ''),
            ],
            'version' => new VersionResource($version),
            'records' => VersionFieldResource::collection($records),
            'groupTypes' => VersionGroupType::withGroupOptions(),
            'fieldTypes' => FormFieldType::withFieldOptions(),
            'inputTypes' => FormInputType::withInputOptions(),
            'statusTypes' => VersionStatus::withTitle(),
            'translations' => $translations,
            'locales' => Util::config('locales')
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function create(Request $request)
    {
        $version = Version::firstOrFailByUuid($request->route('version'));

        return Inertia::render('Genie/Admin/Versions/Fields/CreateEdit', [
            'mode' => 'create',
            'version' => new VersionResource($version),
            'record' => null,
            'groupType' => $request->input('group_type'),
            'groupTypes' => VersionGroupType::withGroupOptions(),
            'fieldTypes' => FormFieldType::withFieldOptions(),
            'inputTypes' => FormInputType::withInputOptions(),
            'statusTypes' => VersionStatus::withTitle()
        ]);
    }

    /**
     * @param StoreVersionField $storeVersionField
     * @return RedirectResponse
     * @throws \Throwable
     */
    public function store(StoreVersionField $storeVersionField)
    {
        $version = Version::firstOrFailByUuid($storeVersionField->route('version'));

        $record = $storeVersionField->handle();

        return redirect()
            ->route('genie.admin.versions.fields.edit', [
                'version' => $version->uuid,
                'field' => $record->uuid,
            ])
            ->with('success', __('genie.field_created'));
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function edit(Request $request)
    {
        $version = Version::firstOrFailByUuid($request->route('version'));

        $record = VersionField::with('options')
            ->where('uuid', $request->route('field'))
            ->firstOrFail();

        return Inertia::render('Genie/Admin/Versions/Fields/CreateEdit', [
            'mode' => 'edit',
            'version' => new VersionResource($version),
            'record' => new VersionFieldResource($record),
            'groupTypes' => VersionGroupType::withGroupOptions(),
            'fieldTypes' => FormFieldType::withFieldOptions(),
            'inputTypes' => FormInputType::withInputOptions(),
            'statusTypes' => VersionStatus::withTitle()
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function translate(Request $request)
    {
        $version = Version::firstOrFailByUuid($request->route('version'));

        $record = VersionField::firstOrFailByUuid($request->route('field'))
            ->setLocale($request->route('locale'));

        $locales = Util::config('locales');
        $locale = Arr::first($locales, function ($value) use ($request) {
            return $value['long'] === $request->route('locale');
        });

        return Inertia::render('Genie/Admin/Versions/Fields/TranslateField', [
            'version' => new VersionResource($version),
            'record' => new VersionFieldResource($record),
            'groupType' => $request->input('group_type'),
            'statusTypes' => VersionStatus::withTitle(),
            'locale' => $locale
        ]);
    }

    /**
     * @param UpdateVersionField $updateVersionField
     * @return RedirectResponse
     * @throws \Throwable
     */
    public function update(UpdateVersionField $updateVersionField)
    {
        $updateVersionField->handle();

        return redirect()->back()->with('success', __('genie.field_updated'));
    }

    /**
     * @param UpdateVersionFieldTranslations $updateVersionField
     * @return RedirectResponse
     * @throws \Throwable
     */
    public function updateTranslations(UpdateVersionFieldTranslations $updateVersionField)
    {
        $updateVersionField->handle();

        return redirect()->back()->with('success', __('genie.field_updated'));
    }

    /**
     * @param UpdateVersionFieldPositions $updateVersionFieldPositions
     * @return JsonResponse
     */
    public function updatePositions(UpdateVersionFieldPositions $updateVersionFieldPositions)
    {
        $updateVersionFieldPositions->handle();

        return response()->json(['message' => __('genie.field_positions_updated')]);
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function destroy(Request $request)
    {
        $result = VersionField::firstOrFailByUuid($request->route('field'))->delete();

        $version = Version::firstOrFailByUuid($request->route('version'));

        $groupType = $request->query('group_type', '');
        if (!$result) {
            return redirect()
                ->route('genie.admin.versions.fields.index', [
                    'version' => $version->uuid,
                    'group_type' => $groupType
                ])
                ->with('error', __('genie.field_not_found'));
        }

        return redirect()->route('genie.admin.versions.fields.index', [
            'version' => $version->uuid,
            'group_type' => $groupType,
        ])
            ->with('success', __('genie.field_deleted'));
    }

    /**
     * @param Collection $records
     * @return array
     */
    public function getTranslations(Collection $records): array
    {
        $translations = [];

        foreach ($records as $record) {
            $recordTranslations = $record->getTranslations();
            foreach ($recordTranslations as $field => $locales) {
                array_walk_recursive($locales, function (&$item) {
                    $item = !empty($item);
                });
                $translations['fields'][$record->uuid][$field] = $locales;
            }
            foreach ($record->options as $option) {
                $optionTranslations = $option->getTranslations();
                foreach ($optionTranslations as $fieldOption => $locales) {
                    array_walk_recursive($locales, function (&$item) {
                        $item = !empty($item);
                    });
                    $translations['options'][$record->uuid][$option->uuid][$fieldOption] = $locales;
                }
            }
        }
        return $translations;
    }
}
