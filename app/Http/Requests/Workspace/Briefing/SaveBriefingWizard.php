<?php

namespace App\Http\Requests\Workspace\Briefing;

use App\Concerns\IngestVersionsContent;
use App\Concerns\Requests\UploadsContentImages;
use App\Enums\BriefingStatus;
use App\Models\Briefing;
use App\Models\Version;
use App\Models\WorkspaceVersion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;

/**
 * The wizard's own save, used both for the answers stored on the way through and for the finish.
 *
 * A workspace holds a single briefing, and the wizard writes on every question, so by the time the
 * customer reaches the last one the record already exists: the two paths have to be one upsert
 * rather than a create and an update the page has to choose between.
 */
class SaveBriefingWizard extends FormRequest
{
    use IngestVersionsContent;
    use UploadsContentImages;

    private array $validationRules;

    private Collection $fieldList;

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return $this->validationRules;
    }

    /**
     * Finishing is the customer pressing Finish, never the wizard storing what they have so far, so
     * only a non-draft request writes the status. A draft leaves it untouched in both directions: a
     * briefing already finished stays finished while it is being re-edited.
     */
    public function handle(): Briefing
    {
        $record = Briefing::latest()->first();

        $draft = $this->boolean('draft');

        $content = $this->getVersionContent()->toArray();

        if (count($this->files) > 0) {
            foreach ($this->processImages() as $fieldName => $imageData) {
                $content[$fieldName] = $imageData;
            }
        }

        $attributes = [
            'content' => $content,
            'version_id' => $this->getVersionId(),
        ];

        if (! $record) {
            return Briefing::create($attributes + [
                'status' => $draft ? BriefingStatus::DRAFT : BriefingStatus::COMPLETE,
            ]);
        }

        $record->update($draft ? $attributes : $attributes + ['status' => BriefingStatus::COMPLETE]);

        return $record;
    }

    /**
     * @throws \Exception
     */
    protected function prepareForValidation(): void
    {
        $versionId = WorkspaceVersion::whereHas('workspace', function ($query) {
            $query->where('uuid', $this->route('workspace'));
        })->firstOrFail()->version_id;

        $this->fieldList = Version::with(['briefings' => ['options']])
            ->find($versionId)
            ->briefings;

        $this->validationRules = $this->getValidationRules($this->boolean('draft'))->toArray();
    }
}
