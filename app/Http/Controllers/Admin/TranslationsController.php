<?php

namespace App\Http\Controllers\Admin;

use App\Ai\Translation\Translator;
use App\Http\Requests\Admin\TranslateAttribute;
use App\Http\Requests\Admin\UpdateTranslations;
use App\Support\Translatables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Inovector\Mixpost\Util;
use Throwable;

/**
 * Inline translation of one translatable attribute of one record.
 *
 * Which records qualify is decided by {@see Translatables}; the controller itself is
 * model-agnostic.
 */
class TranslationsController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $record = Translatables::resolve($request->route('type'), $request->route('record'));
        $attribute = $request->route('attribute');

        Translatables::assertAttribute($record, $attribute);

        $sourceLocale = Translatables::sourceLocale();

        return response()->json([
            'source_locale' => $sourceLocale,
            'source' => Translatables::sourceOf($record, $attribute),
            'translations' => Translatables::translationsOf($record, $attribute),
            'locales' => Util::config('locales'),
        ]);
    }

    public function update(UpdateTranslations $updateTranslations): JsonResponse
    {
        return response()->json([
            'message' => __('genie.translation_updated'),
            'translations' => $updateTranslations->handle(),
        ]);
    }

    public function translate(TranslateAttribute $translateAttribute, Translator $translator): JsonResponse
    {
        try {
            $translations = $translateAttribute->handle($translator);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return response()->json(['message' => __('genie.translation_failed')], 422);
        }

        return response()->json([
            'message' => __('genie.translated'),
            'translations' => $translations,
        ]);
    }
}
