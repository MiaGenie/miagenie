<?php

namespace App\Models;

use App\Enums\ModelTier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Inovector\Mixpost\Concerns\Model\HasUuid;
use Laravel\Ai\Enums\Lab;

/**
 * A reusable provider + model configuration that rule steps point at, so tuning does not
 * mean editing every step row.
 */
class ModelProfile extends Model
{
    use HasUuid;

    /**
     * @var string
     */
    protected $table = 'genie_model_profiles';

    /**
     * @var string[]
     */
    protected $fillable = [
        'uuid',
        'name',
        'provider',
        'model_tier',
        'model',
        'timeout',
        'position',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'model_tier' => ModelTier::class,
        'timeout' => 'integer',
    ];

    /**
     * The provider as the SDK's enum, falling back to the raw string so an OpenAI-compatible
     * provider named in config/ai.php still resolves.
     */
    public function lab(): Lab|string
    {
        return Lab::tryFrom($this->provider) ?? $this->provider;
    }

    /**
     * The model this profile names outright, or null when the SDK resolves it from the tier.
     */
    public function explicitModel(): ?string
    {
        return $this->model_tier === ModelTier::OTHER ? $this->model : null;
    }

    public function steps(): HasMany
    {
        return $this->hasMany(RuleStep::class, 'model_profile_id');
    }
}
