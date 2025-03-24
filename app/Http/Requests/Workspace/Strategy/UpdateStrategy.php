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
        $record = Strategy::firstOrFailByUuid($this->route('strategy'));

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
            ->with(['strategies' => ['options']])
            ->firstOrFail()
            ->strategies;

        $this->validationRules = $this->getValidationRules()->toArray();
    }
}
