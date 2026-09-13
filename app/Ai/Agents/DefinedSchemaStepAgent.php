<?php

namespace App\Ai\Agents;

use App\Genie\Runs\SchemaDefinition;
use App\Models\RuleStep;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Attributes\Strict;

/**
 * A step whose structured output is defined in code rather than compiled from the version.
 *
 * `#[Strict]` is declared again rather than inherited: the SDK reads the attribute off the
 * concrete class, and PHP does not carry class attributes down to subclasses.
 */
#[Strict]
class DefinedSchemaStepAgent extends StructuredStepAgent
{
    public function __construct(
        RuleStep $step,
        string $locale,
        protected SchemaDefinition $definition,
    ) {
        parent::__construct($step, $locale);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return $this->definition->properties($schema);
    }
}
