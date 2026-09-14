<?php

namespace Tests\Unit;

use App\Genie\Posts\PostSchema;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Laravel\Ai\ObjectSchema;
use Tests\TestCase;

class PostSchemaTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    protected function compile(PostSchema $schema): array
    {
        return new ObjectSchema($schema->properties(new JsonSchemaTypeFactory), 'post', strict: true)->toSchema();
    }

    public function test_the_post_has_a_required_caption_and_a_nullable_title(): void
    {
        $schema = $this->compile(PostSchema::forRun('en-GB'));

        $this->assertSame(['post'], $schema['required']);

        $post = $schema['properties']['post'];

        $this->assertSame('object', $post['type']);
        $this->assertSame(['caption', 'title'], array_keys($post['properties']));
        $this->assertSame(['caption', 'title'], $post['required']);
        $this->assertFalse($post['additionalProperties']);
        $this->assertSame('string', $post['properties']['caption']['type']);
        $this->assertContains('null', (array) $post['properties']['title']['type']);
    }

    public function test_every_node_is_annotated_in_the_workspace_locale(): void
    {
        $post = $this->compile(PostSchema::preview('en-GB'))['properties']['post'];

        $this->assertSame(__('genie.post', [], 'en-GB'), $post['title']);
        $this->assertSame(__('genie.post_caption', [], 'en-GB'), $post['properties']['caption']['title']);
        $this->assertSame(__('genie.post_caption_desc', [], 'en-GB'), $post['properties']['caption']['description']);
        $this->assertSame(__('genie.post_title_desc', [], 'en-GB'), $post['properties']['title']['description']);

        $portuguese = $this->compile(PostSchema::preview('pt-PT'))['properties']['post'];

        $this->assertSame(__('genie.post_caption', [], 'pt-PT'), $portuguese['properties']['caption']['title']);
    }
}
