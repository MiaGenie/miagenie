<?php

namespace App\Http\Requests\Workspace\Competitor;

use App\Concerns\IngestVersionsContent;
use App\Models\Competitor;
use App\Models\Version;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;

class StoreCompetitor extends FormRequest
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
     * @return Competitor
     */
    public function handle(): Competitor
    {
        return Competitor::create([
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
            ->with(['fields' => ['options']])
            ->firstOrFail()
            ->fields;

        $this->validationRules = $this->getValidationRules()->toArray();
    }
}
