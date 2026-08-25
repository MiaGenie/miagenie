<?php

namespace Tests\Unit;

use App\Concerns\Controller\HasFieldOptions;
use App\Concerns\Controller\HasWorkspaceLocale;
use App\Models\VersionField;
use App\Models\VersionFieldOption;
use App\Models\Workspace;
use Illuminate\Support\Collection;
use Inovector\Mixpost\Facades\WorkspaceManager;
use Tests\TestCase;

/**
 * Field content follows the workspace's language, not the language the signed-in user reads the
 * app in, so every case here runs with the app locale pinned to English.
 */
class WorkspaceLocaleFieldsTest extends TestCase
{
    use HasFieldOptions;
    use HasWorkspaceLocale;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('en-GB');
    }

    protected function tearDown(): void
    {
        WorkspaceManager::forgetCurrent();

        parent::tearDown();
    }

    protected function workspaceWithLocale(?string $locale): void
    {
        WorkspaceManager::setCurrent(new Workspace(['name' => 'Test', 'locale' => $locale]));
    }

    /**
     * @param  array<string, string|array<string, string>>  $attributes
     * @param  array<int, VersionFieldOption>|null  $options
     */
    protected function field(array $attributes, ?array $options = null): VersionField
    {
        $field = new VersionField(array_merge(['code_name' => 'brand_name'], $attributes));

        if ($options !== null) {
            $field->setRelation('options', new Collection($options));
        }

        return $field;
    }

    public function test_a_field_is_read_in_the_workspace_locale_rather_than_the_app_locale(): void
    {
        $this->workspaceWithLocale('pt-PT');

        $fields = $this->localizedFields([
            $this->field([
                'name' => ['en-GB' => 'Brand Name', 'pt-PT' => 'Nome da Marca'],
                'description' => ['en-GB' => 'What is it called?', 'pt-PT' => 'Como se chama?'],
                'sub_description' => ['en-GB' => 'One name', 'pt-PT' => 'Um nome'],
            ]),
        ]);

        $this->assertSame('Nome da Marca', $fields[0]['name']);
        $this->assertSame('Como se chama?', $fields[0]['description']);
        $this->assertSame('Um nome', $fields[0]['sub_description']);
        $this->assertSame('en-GB', app()->getLocale());
    }

    public function test_option_labels_are_translated_and_survive_grouping(): void
    {
        $this->workspaceWithLocale('pt-PT');

        $fields = $this->groupFieldOptions($this->localizedFields([
            $this->field([
                'name' => ['en-GB' => 'Evoked Emotions', 'pt-PT' => 'Emoções Evocadas'],
            ], [
                new VersionFieldOption([
                    'code_name' => 'confidence',
                    'name' => ['en-GB' => 'Confidence', 'pt-PT' => 'Confiança'],
                    'group' => 0,
                    'position' => 0,
                ]),
                new VersionFieldOption([
                    'code_name' => 'excitement',
                    'name' => ['en-GB' => 'Excitement', 'pt-PT' => 'Entusiasmo'],
                    'group' => 0,
                    'position' => 1,
                ]),
            ]),
        ]));

        $this->assertSame('Emoções Evocadas', $fields[0]['name']);
        $this->assertSame('Confiança', $fields[0]['options'][0][0]['name']);
        $this->assertSame('Entusiasmo', $fields[0]['options'][0][1]['name']);
        $this->assertSame('confidence', $fields[0]['options'][0][0]['code_name']);
    }

    public function test_a_translation_the_workspace_locale_is_missing_falls_back_to_the_original(): void
    {
        $this->workspaceWithLocale('pt-PT');

        $fields = $this->localizedFields([
            $this->field(['name' => ['en-GB' => 'Brand Name']]),
        ]);

        $this->assertSame('Brand Name', $fields[0]['name']);
    }

    public function test_a_workspace_without_a_locale_reads_in_the_fallback_locale(): void
    {
        $this->workspaceWithLocale(null);

        $fields = $this->localizedFields([
            $this->field(['name' => ['en-GB' => 'Brand Name', 'pt-PT' => 'Nome da Marca']]),
        ]);

        $this->assertSame(config('app.fallback_locale'), $this->workspaceLocale());
        $this->assertSame('Brand Name', $fields[0]['name']);
    }
}
