<?php

namespace Tests\Unit;

use App\Enums\SubFieldType;
use App\Http\Resources\Admin\VersionFieldSubFieldResource;
use App\Models\VersionFieldSubField;
use Tests\TestCase;

class VersionFieldSubFieldResourceTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function resource(array $attributes): array
    {
        $subField = new VersionFieldSubField(array_merge([
            'sub_code_name' => 'tone',
            'type' => SubFieldType::STRING,
        ], $attributes));

        return (new VersionFieldSubFieldResource($subField))->toArray(request());
    }

    public function test_the_allowed_values_are_the_current_locale_list(): void
    {
        $this->assertSame(
            ['formal', 'casual'],
            $this->resource(['enum_values' => ['en-GB' => ['formal', 'casual'], 'pt-PT' => ['formal', 'informal']]])['enum_values']
        );
    }

    public function test_cleared_or_missing_allowed_values_are_an_empty_list(): void
    {
        // A cleared translation reads back from spatie as '', which the editor must never receive.
        $this->assertSame([], $this->resource(['enum_values' => ['en-GB' => null]])['enum_values']);
        $this->assertSame([], $this->resource([])['enum_values']);
    }
}
