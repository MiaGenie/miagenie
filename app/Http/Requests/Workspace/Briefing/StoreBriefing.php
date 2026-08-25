<?php

namespace App\Http\Requests\Workspace\Briefing;

use App\Concerns\IngestVersionsContent;
use App\Concerns\Requests\UploadsContentImages;
use App\Models\Briefing;
use App\Models\Version;
use App\Models\WorkspaceVersion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;

class StoreBriefing extends FormRequest
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

    public function handle(): Briefing
    {
        $record = Briefing::create([
            'content' => $this->getVersionContent()->toArray(),
            'version_id' => $this->getVersionId(),
        ]);

        $content = $this->getVersionContent()->toArray();

        if (count($this->files) > 0) {
            $imageFields = $this->processImages();
            foreach ($imageFields as $fieldName => $imageData) {
                $content[$fieldName] = $imageData;
            }
        }

        $record->update([
            'content' => $content,
        ]);

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

        $this->validationRules = $this->getValidationRules()->toArray();
    }
}
