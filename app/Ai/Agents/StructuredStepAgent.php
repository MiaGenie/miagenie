<?php

namespace App\Ai\Agents;

use App\Genie\Schema\StepSchemaBuilder;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Contracts\HasStructuredOutput;

/**
 * A step that returns structured output.
 */
#[Strict]
class StructuredStepAgent extends StepAgent implements HasStructuredOutput
{
    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return new StepSchemaBuilder($schema)->propertiesForStep($this->step, $this->locale);
    }
}
