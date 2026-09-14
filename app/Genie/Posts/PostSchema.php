<?php

namespace App\Genie\Posts;

use App\Genie\Runs\SchemaDefinition;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;

/**
 * What a posts text step returns: the caption every account publishes, and a title for the
 * providers that show one.
 *
 * Strict mode lists every property as required, so the title is nullable rather than optional.
 */
class PostSchema implements SchemaDefinition
{
    public const ROOT = 'post';

    public function __construct(protected string $locale) {}

    public static function forRun(string $locale): self
    {
        return new self($locale);
    }

    public static function preview(string $locale): self
    {
        return new self($locale);
    }

    /**
     * @return array<string, Type>
     */
    public function properties(JsonSchema $schema): array
    {
        return [
            self::ROOT => $schema->object([
                'caption' => $schema->string()
                    ->title($this->text('post_caption'))
                    ->description($this->text('post_caption_desc'))
                    ->required(),
                'title' => $schema->string()
                    ->title($this->text('post_title'))
                    ->description($this->text('post_title_desc'))
                    ->required()
                    ->nullable(),
            ])
                ->title($this->text('post'))
                ->description($this->text('post_schema_desc'))
                ->required(),
        ];
    }

    protected function text(string $key): string
    {
        return (string) __('genie.'.$key, [], $this->locale);
    }
}
