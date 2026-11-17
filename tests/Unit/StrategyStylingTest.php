<?php

namespace Tests\Unit;

use App\Concerns\Requests\ValidatesStrategyStyling;
use App\Enums\FormFieldType;
use App\Enums\StrategyBlock;
use App\Enums\StrategyStyle;
use App\Enums\StrategyStyleCategory;
use App\Enums\SubFieldType;
use App\Enums\VersionGroupType;
use App\Http\Requests\Admin\StoreVersionField;
use App\Http\Requests\Admin\UpdateVersionField;
use App\Rules\StrategyStyleTokens;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * The strategy styling vocabulary — sub-field blocks, section blocks and style tokens — and how
 * the field forms validate and store it. The suite has no migrated database, so the version the
 * forms look up and the fields table their unique code name rule reads are built in memory.
 */
class StrategyStylingTest extends TestCase
{
    protected const VERSION = 'version-uuid';

    protected const FIELD = 'field-uuid';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.connections.strategy_styling', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        config()->set('database.default', 'strategy_styling');

        Schema::create('genie_versions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->timestamps();
        });

        Schema::create('genie_version_fields', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('version_id');
            $table->tinyInteger('group_type');
            $table->string('code_name');
            $table->timestamps();
        });

        DB::table('genie_versions')->insert(['id' => 1, 'uuid' => self::VERSION]);
        DB::table('genie_version_fields')->insert([
            'id' => 1,
            'uuid' => self::FIELD,
            'version_id' => 1,
            'group_type' => VersionGroupType::STRATEGIES->value,
            'code_name' => 'big_idea',
        ]);
    }

    /**
     * A strategy field form that is valid apart from what a test overrides, bound to the admin
     * route it is posted to.
     *
     * @param  class-string<FormRequest>  $form
     * @param  array<string, mixed>  $input
     */
    protected function request(array $input = [], string $form = StoreVersionField::class): FormRequest
    {
        $update = $form === UpdateVersionField::class;
        $uri = 'genie/admin/versions/'.self::VERSION.'/fields'.($update ? '/'.self::FIELD : '');

        $request = $form::create($uri, $update ? 'PUT' : 'POST', array_merge([
            'group_type' => VersionGroupType::STRATEGIES->value,
            'field_type' => FormFieldType::JSON_SCHEMA->value,
            'name' => 'Big idea',
            'code_name' => $update ? 'big_idea' : 'brand_story',
            'options' => [[]],
            'sub_fields' => [],
        ], $input));

        $route = (new Route($update ? 'PUT' : 'POST', 'genie/admin/versions/{version}/fields'.($update ? '/{field}' : ''), []))
            ->bind($request);

        $request->setRouteResolver(fn () => $route);
        $request->setContainer($this->app)->setRedirector($this->app['redirect']);

        return $request;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, list<string>>
     */
    protected function errors(array $input, string $form = StoreVersionField::class): array
    {
        try {
            $this->request($input, $form)->validateResolved();
        } catch (ValidationException $exception) {
            return $exception->errors();
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  class-string<FormRequest>  $form
     * @return array{block: string|null, class: string|null}
     */
    protected function attributes(array $input, string $form = StoreVersionField::class): array
    {
        $request = $this->request($input, $form);
        $request->validateResolved();

        return (fn () => $this->stylingAttributes())->call($request);
    }

    /**
     * @param  array<int, array<string, mixed>>  $children
     * @return array<string, mixed>
     */
    protected function subField(string $codeName, SubFieldType $type, ?string $block = null, array $children = []): array
    {
        return [
            'name' => ucfirst($codeName),
            'sub_code_name' => $codeName,
            'type' => (string) $type->value,
            'block' => $block,
            'children' => $children,
        ];
    }

    protected function passesTokens(mixed $value): bool
    {
        return Validator::make(['class' => $value], ['class' => ['nullable', new StrategyStyleTokens]])->passes();
    }

    public function test_each_block_fits_its_own_shape_only(): void
    {
        $string = [SubFieldType::STRING];
        $boolean = [SubFieldType::BOOLEAN];

        $this->assertTrue(StrategyBlock::STACK->fits(SubFieldType::OBJECT, $string));
        $this->assertTrue(StrategyBlock::CARDS->fits(SubFieldType::OBJECT, $string));
        $this->assertTrue(StrategyBlock::CHOICES->fits(SubFieldType::OBJECT, [SubFieldType::BOOLEAN, SubFieldType::BOOLEAN]));
        $this->assertFalse(StrategyBlock::CHOICES->fits(SubFieldType::OBJECT, [SubFieldType::BOOLEAN, SubFieldType::STRING]));
        $this->assertFalse(StrategyBlock::CHOICES->fits(SubFieldType::OBJECT, []));

        $this->assertTrue(StrategyBlock::BULLETS->fits(SubFieldType::ARRAY, []));
        $this->assertFalse(StrategyBlock::BULLETS->fits(SubFieldType::ARRAY, $string));
        $this->assertTrue(StrategyBlock::NUMBERED_CARDS->fits(SubFieldType::ARRAY, $string));
        $this->assertFalse(StrategyBlock::NUMBERED_CARDS->fits(SubFieldType::ARRAY, []));

        $this->assertTrue(StrategyBlock::CHECKBOX->fits(SubFieldType::BOOLEAN, []));
        $this->assertTrue(StrategyBlock::TEXT->fits(SubFieldType::STRING, []));

        $this->assertFalse(StrategyBlock::TEXT->fits(SubFieldType::BOOLEAN, []));
        $this->assertFalse(StrategyBlock::CARDS->fits(SubFieldType::ARRAY, $boolean));
    }

    public function test_a_posted_row_resolves_its_block_from_its_type_and_children(): void
    {
        $title = $this->subField('title', SubFieldType::STRING);

        $this->assertSame(StrategyBlock::NUMBERED_CARDS, StrategyBlock::forRow($this->subField('pillars', SubFieldType::ARRAY, 'numbered_cards', [$title])));
        $this->assertNull(StrategyBlock::forRow($this->subField('pillars', SubFieldType::ARRAY, 'bullets', [$title])));
        $this->assertNull(StrategyBlock::forRow($this->subField('about', SubFieldType::STRING, 'group')));
        $this->assertNull(StrategyBlock::forRow($this->subField('about', SubFieldType::STRING)));

        // Children a string type still posts are ignored, as the save ignores them.
        $this->assertSame(StrategyBlock::TEXT, StrategyBlock::forRow($this->subField('about', SubFieldType::STRING, 'text', [$title])));
    }

    public function test_sanitize_keeps_the_first_known_token_of_each_category(): void
    {
        $this->assertSame(
            'strategy-tone-cyan strategy-animate-fade-in',
            StrategyStyle::sanitize('  strategy-tone-cyan text-red-500 strategy-tone-orange   strategy-animate-fade-in '),
        );
        $this->assertNull(StrategyStyle::sanitize('text-red-500 group'));
        $this->assertNull(StrategyStyle::sanitize(''));
        $this->assertNull(StrategyStyle::sanitize(null));
    }

    public function test_every_category_offers_its_tokens_to_the_editor(): void
    {
        $editor = StrategyStyle::forEditor();

        $this->assertSame(
            array_map(fn (StrategyStyleCategory $category) => $category->value, StrategyStyleCategory::cases()),
            $editor->pluck('category')->all(),
        );
        $this->assertSame(
            count(StrategyStyle::cases()),
            $editor->sum(fn (array $group) => count($group['options'])),
        );
    }

    public function test_the_token_rule_accepts_one_known_token_per_category(): void
    {
        $this->assertTrue($this->passesTokens('strategy-tone-orange strategy-emphasis-highlight strategy-animate-slide-up'));
        $this->assertTrue($this->passesTokens(null));
        $this->assertTrue($this->passesTokens(''));

        $this->assertFalse($this->passesTokens('strategy-tone-orange text-red-500'));
        $this->assertFalse($this->passesTokens('strategy-tone-orange strategy-tone-cyan'));
        $this->assertFalse($this->passesTokens(['strategy-tone-orange']));
    }

    public function test_a_strategy_field_stores_its_section_and_style_tokens(): void
    {
        $input = ['block' => 'feature', 'class' => 'strategy-animate-fade-in  strategy-tone-orange'];

        $expected = ['block' => 'feature', 'class' => 'strategy-animate-fade-in strategy-tone-orange'];

        $this->assertSame($expected, $this->attributes($input));
        $this->assertSame($expected, $this->attributes($input, UpdateVersionField::class));
    }

    public function test_a_briefing_field_stores_no_styling(): void
    {
        $input = [
            'group_type' => VersionGroupType::BRIEFINGS->value,
            'block' => 'feature',
            'class' => 'strategy-tone-orange',
        ];

        $this->assertSame(['block' => null, 'class' => null], $this->attributes($input));
        $this->assertSame(['block' => null, 'class' => null], $this->attributes($input, UpdateVersionField::class));
    }

    public function test_a_form_without_styling_stores_none(): void
    {
        $this->assertSame(['block' => null, 'class' => null], $this->attributes([]));
        $this->assertSame(['block' => null, 'class' => null], $this->attributes(['block' => '', 'class' => '']));
    }

    public function test_an_unknown_section_or_style_is_refused(): void
    {
        $this->assertArrayHasKey('block', $this->errors(['block' => 'group']));
        $this->assertArrayHasKey('block', $this->errors(['block' => 'cards']));
        $this->assertArrayHasKey('class', $this->errors(['class' => 'bg-red-50']));
        $this->assertArrayHasKey('class', $this->errors(['class' => 'strategy-tone-orange strategy-tone-cyan'], UpdateVersionField::class));
    }

    public function test_a_sub_field_block_must_fit_at_any_depth(): void
    {
        $nested = $this->subField('tone_of_voice', SubFieldType::OBJECT, 'cards', [
            $this->subField('dos', SubFieldType::ARRAY, 'bullets'),
            $this->subField('pillars', SubFieldType::ARRAY, 'bullets', [
                $this->subField('title', SubFieldType::STRING),
            ]),
        ]);

        $errors = $this->errors(['sub_fields' => [$nested]]);

        $this->assertArrayNotHasKey('sub_fields.0.block', $errors);
        $this->assertArrayNotHasKey('sub_fields.0.children.0.block', $errors);
        $this->assertArrayHasKey('sub_fields.0.children.1.block', $errors);
    }

    public function test_a_sub_field_block_for_another_type_or_an_unknown_one_is_refused(): void
    {
        $errors = $this->errors(['sub_fields' => [
            $this->subField('about', SubFieldType::STRING, 'checkbox'),
            $this->subField('dos', SubFieldType::ARRAY, 'group'),
        ]]);

        $this->assertArrayHasKey('sub_fields.0.block', $errors);
        $this->assertArrayHasKey('sub_fields.1.block', $errors);
    }

    public function test_sub_field_style_tokens_are_validated(): void
    {
        $about = $this->subField('about', SubFieldType::STRING);

        $this->assertArrayNotHasKey('sub_fields.0.class', $this->errors(['sub_fields' => [[...$about, 'class' => 'strategy-emphasis-highlight']]]));
        $this->assertArrayHasKey('sub_fields.0.class', $this->errors(['sub_fields' => [[...$about, 'class' => 'font-bold']]]));
    }

    public function test_only_the_strategy_group_supports_styling(): void
    {
        foreach (VersionGroupType::cases() as $groupType) {
            $this->assertSame($groupType === VersionGroupType::STRATEGIES, $groupType->supportsStyling(), $groupType->name);
        }
    }

    public function test_both_field_forms_validate_the_styling(): void
    {
        $this->assertContains(ValidatesStrategyStyling::class, class_uses(StoreVersionField::class));
        $this->assertContains(ValidatesStrategyStyling::class, class_uses(UpdateVersionField::class));
    }
}
