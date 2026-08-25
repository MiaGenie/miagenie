<?php

namespace App\Http\Requests\Workspace\Strategy;

use App\Concerns\IngestVersionsContent;
use App\Models\Strategy;
use App\Models\Version;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;

class UpdateStrategy extends FormRequest
{
    use IngestVersionsContent;

    private array $validationRules;

    private Collection $fieldList;

    private ?Strategy $strategy = null;

    /**
     * @return array
     */
    /*    public function rules(): array
        {
            return $this->validationRules;
        }*/

    /**
     * Write the edited fields back.
     *
     * What the request omits keeps its stored value — the presentation only sends the fields the
     * run wrote — and a payload cannot introduce a key the version does not define.
     */
    public function handle(): bool
    {
        $strategy = $this->strategy();

        return $strategy->update([
            'content' => array_merge(
                $strategy->content ?? [],
                array_intersect_key(
                    (array) $this->input('content'),
                    array_flip($this->fieldList->pluck('code_name')->all())
                )
            ),
        ]);
    }

    /**
     * @throws \Exception
     */
    protected function prepareForValidation(): void
    {
        $this->fieldList = $this->version()->strategies()->with('options')->get();

        //        $this->validationRules = $this->getValidationRules()->toArray();
    }

    /**
     * The strategy being edited.
     *
     * Read from the route rather than the payload: every page posting here carries `{strategy}`,
     * while only the older forms happen to send the record's `id` in the body.
     */
    private function strategy(): Strategy
    {
        return $this->strategy ??= Strategy::firstOrFailByUuid($this->route('strategy'));
    }

    /**
     * The strategy's own version, which is what its `code_name`s belong to — the same name exists
     * in several versions. A strategy from before the current pipeline has no `aiRun`, so it falls
     * back to the version the workspace is on.
     */
    private function version(): Version
    {
        return $this->strategy()->aiRun?->rule?->version
            ?? Version::findOrFail($this->getVersionId());
    }
}
