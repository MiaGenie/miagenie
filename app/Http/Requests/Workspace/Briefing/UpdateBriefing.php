<?php

namespace App\Http\Requests\Workspace\Briefing;

use App\Concerns\IngestVersionsContent;
use App\Concerns\Requests\UploadsContentImages;
use App\Models\Briefing;
use App\Models\Version;
use App\Models\WorkspaceVersion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;

class UpdateBriefing extends FormRequest
{
    use IngestVersionsContent;
    use UploadsContentImages;

    private array $validationRules;

    private Collection $fieldList;

    /**
     * @return array[]
     */
    public function rules(): array
    {
        return $this->validationRules;
    }

    public function handle(): int
    {
        $record = Briefing::firstOrFailByUuid($this->route('briefing'));

        $content = $this->getVersionContent()->toArray();

        if (count($this->files) > 0) {
            $imageFields = $this->processImages();
            foreach ($imageFields as $fieldName => $imageData) {
                $content[$fieldName] = $imageData;
            }
        }

        return $record->update([
            'content' => $content,
            'version_id' => $this->getVersionId(),
        ]);
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

        $this->validationRules = $this->getValidationRules()->toArray();
    }
}
