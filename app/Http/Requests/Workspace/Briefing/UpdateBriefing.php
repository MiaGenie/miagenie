<?php

namespace App\Http\Requests\Workspace\Briefing;

use App\Concerns\IngestVersionsContent;
use App\Models\Briefing;
use App\Models\Version;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;

class UpdateBriefing extends FormRequest
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
     * @return array[]
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
        $record = Briefing::firstOrFailByUuid($this->route('briefing'));

        return $record->update([
            'content' => $this->getVersionContent()->toArray(),
            'version_id' => $this->getVersionId()
        ]);
    }

    /**
     * @return void
     * @throws \Exception
     */
    protected function prepareForValidation(): void
    {
        $this->fieldList = Version::findByUuid($this->input('version'))
            ->with(['briefings' => ['options']])
            ->firstOrFail()
            ->briefings;

        $this->validationRules = $this->getValidationRules()->toArray();
    }
}
