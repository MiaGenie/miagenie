<?php

namespace App\Http\Requests\Workspace\Competitor;

use App\Concerns\IngestVersionsContent;
use App\Models\Competitor;
use App\Models\Version;
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
            ->with(['competitors' => ['options']])
            ->firstOrFail()
            ->competitors;

        $this->validationRules = $this->getValidationRules()->toArray();
    }
}
