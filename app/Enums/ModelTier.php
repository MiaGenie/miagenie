<?php

namespace App\Enums;

use App\Concerns\Enum\WithTitle;
use Laravel\Ai\Contracts\Providers\TextProvider;

/**
 * How a model profile chooses its text model.
 *
 * Every SDK provider exposes the same three tiers — the #[UseCheapestModel] and
 * #[UseSmartestModel] attributes read exactly these accessors — so a profile can follow
 * whatever `config/ai.php` names for its provider instead of pinning a model that goes
 * stale. OTHER is the escape hatch: the profile names the model itself.
 */
enum ModelTier: string
{
    use WithTitle;

    case DEFAULT = 'default';
    case CHEAPEST = 'cheapest';
    case SMARTEST = 'smartest';
    case OTHER = 'other';

    public function title(): string
    {
        return match ($this) {
            self::DEFAULT => 'default',
            self::CHEAPEST => 'cheapest',
            self::SMARTEST => 'smartest',
            self::OTHER => 'other',
        };
    }

    /**
     * The model this tier resolves to for a provider, or null when the profile names its own.
     */
    public function textModelFor(TextProvider $provider): ?string
    {
        return match ($this) {
            self::DEFAULT => $provider->defaultTextModel(),
            self::CHEAPEST => $provider->cheapestTextModel(),
            self::SMARTEST => $provider->smartestTextModel(),
            self::OTHER => null,
        };
    }
}
