<?php

namespace App\Genie\Ideas;

use App\Enums\FunnelStage;
use App\Enums\PostFormat;
use App\Genie\Runs\PostFormatGate;
use App\Models\AiRun;

/**
 * What an ideas run was asked for, read from and written to the run's `parameters`.
 *
 * Null for the format or the pillar means "any": every format step runs, every pillar is offered.
 * GLOBAL for the stage means the same for the funnel — the model chooses per idea.
 */
final class IdeasRequest
{
    /**
     * The totals the page offers.
     *
     * @var list<int>
     */
    public const COUNTS = [5, 10, 20];

    public const DEFAULT_COUNT = 10;

    public function __construct(
        public readonly ?PostFormat $postFormat,
        public readonly ?string $contentPillar,
        public readonly FunnelStage $funnelStage,
        public readonly int $count,
    ) {}

    /**
     * Tolerant of a missing or malformed key: a run created before a parameter existed still reads.
     *
     * @param  array<string, mixed>  $parameters
     */
    public static function fromArray(array $parameters): self
    {
        $format = $parameters[PostFormatGate::PARAMETER] ?? null;
        $stage = $parameters['funnel_stage'] ?? null;
        $pillar = $parameters['content_pillar'] ?? null;
        $count = $parameters['count'] ?? null;

        return new self(
            is_numeric($format) ? PostFormat::tryFrom((int) $format) : null,
            is_string($pillar) && filled($pillar) ? $pillar : null,
            (is_numeric($stage) ? FunnelStage::tryFrom((int) $stage) : null) ?? FunnelStage::GLOBAL,
            is_numeric($count) && (int) $count > 0 ? (int) $count : self::DEFAULT_COUNT,
        );
    }

    public static function fromRun(AiRun $run): self
    {
        return self::fromArray($run->parameters ?? []);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            PostFormatGate::PARAMETER => $this->postFormat?->value,
            'content_pillar' => $this->contentPillar,
            'funnel_stage' => $this->funnelStage->value,
            'count' => $this->count,
        ];
    }

    public function wantsAnyFormat(): bool
    {
        return $this->postFormat === null;
    }

    public function wantsAnyPillar(): bool
    {
        return $this->contentPillar === null;
    }

    public function wantsAnyStage(): bool
    {
        return $this->funnelStage->isAny();
    }

    /**
     * How the total is split between the steps that will generate.
     *
     * Exact rather than rounded up: 5 ideas over 4 formats is 2, 1, 1, 1 — the first steps take
     * the remainder — so the customer gets the number they asked for, not a multiple of it.
     *
     * @return list<int>
     */
    public function shares(int $generators): array
    {
        if ($generators <= 0) {
            return [];
        }

        $base = intdiv($this->count, $generators);
        $remainder = $this->count % $generators;

        $shares = [];

        for ($index = 0; $index < $generators; $index++) {
            $shares[] = $base + ($index < $remainder ? 1 : 0);
        }

        return $shares;
    }

    /**
     * The share of one step, by its position among the generators.
     */
    public function shareFor(int $index, int $generators): int
    {
        return $this->shares($generators)[$index] ?? 0;
    }
}
