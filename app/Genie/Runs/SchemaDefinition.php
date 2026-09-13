<?php

namespace App\Genie\Runs;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;

/**
 * A structured-output schema defined in code rather than compiled from a version's sub-fields.
 *
 * A strategy step's contract is whatever the admin drew as a sub-field tree; an ideas step's — and
 * later a posts step's — is fixed by the model that stores the result, so it is stated here and
 * handed to the agent as-is.
 */
interface SchemaDefinition
{
    /**
     * The root object's properties.
     *
     * @return array<string, Type>
     */
    public function properties(JsonSchema $schema): array;
}
