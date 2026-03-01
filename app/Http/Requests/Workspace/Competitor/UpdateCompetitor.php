<?php

namespace App\Http\Requests\Workspace\Competitor;

use App\Concerns\IngestVersionsContent;
use App\Enums\WorkspaceFileSource;
use App\Enums\WorkspaceFileType;
use App\Models\Competitor;
use App\Models\Version;
use App\Models\WorkspaceVersion;
use App\Support\FileUploader;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;

class UpdateCompetitor extends FormRequest
{
    use IngestVersionsContent;

    /**
     * @var array
     */
    private array $validationRules;

    /**
     * @var Collection
     */
    private Collection $fieldList;

    /**
     * @return array
     */
    public function rules(): array
    {
        return $this->validationRules;
    }

    /**
     * @return int
     */
    public function handle(): int
    {
        $record = Competitor::firstOrFailByUuid($this->route('competitor'));

        $content = $this->getVersionContent()->toArray();

        if (sizeof($this->files) > 0) {
            $imageFields = $this->processImages();
            foreach ($imageFields as $fieldName => $imageData) {
                $content[$fieldName] = $imageData;
            }
        }

        return $record->update([
            'content' => $this->getVersionContent()->toArray(),
            'version_id' => $this->getVersionId()
        ]);
    }

    /**
     * @return array
     */
    private function processImages(): array
    {
        $files = [];
        foreach ($this->files as $fields) {
            foreach ($fields as $field => $fieldFiles) {
                $i = 0;
                foreach ($fieldFiles as $file) {
                    $record = FileUploader::createFromBase($file)
                        ->path('workspace/' . $this->route('workspace') . '/images')
                        ->disk('public')
                        ->uploadAndInsertWorkspaceFile(
                            WorkspaceFileType::BRIEFING,
                            WorkspaceFileSource::USER
                        );
                    $files[$field][$i]['id'] = $record->name;
                    $files[$field][$i]['path'] = $record->getUrl();
                    $i++;
                }
                $files[$field] = sizeof($fieldFiles) === 1 ? $files[$field][0] : $files[$field];
            }
        }


        return $files;
    }

    /**
     * @return void
     * @throws \Exception
     */
    protected function prepareForValidation(): void
    {
        $versionId = WorkspaceVersion::whereHas('workspace', function ($query) {
            $query->where('uuid', $this->route('workspace'));
        })->firstOrFail()->version_id;

        $this->fieldList = Version::with(['competitors' => ['options']])
            ->find($versionId)
            ->competitors;

        $this->validationRules = $this->getValidationRules()->toArray();
    }
}
