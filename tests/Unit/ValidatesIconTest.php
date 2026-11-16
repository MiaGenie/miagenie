<?php

namespace Tests\Unit;

use App\Concerns\Requests\ValidatesIcon;
use App\Enums\FormFieldType;
use App\Enums\VersionGroupType;
use App\Http\Requests\Admin\StoreVersionField;
use App\Http\Requests\Admin\UpdateVersionField;
use App\Models\VersionField;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * The icon rules, exercised through the real field forms. The suite has no migrated database, so
 * the version the forms look up and the fields table their unique code name rule reads are built
 * in memory.
 */
class ValidatesIconTest extends TestCase
{
    protected const VERSION = 'version-uuid';

    protected const FIELD = 'field-uuid';

    /**
     * `mdiRocketLaunch` from @mdi/js, as the picker sends it.
     */
    protected const ROCKET_PATH = 'M13.13 22.19L11.5 18.36C13.07 17.78 14.54 17 15.9 16.09L13.13 22.19M5.64 12.5L1.81 10.87L7.91 8.1C7 9.46 6.22 10.93 5.64 12.5M21.61 2.39C21.61 2.39 16.66 .269 11 5.93C8.81 8.12 7.5 10.53 6.65 12.64C6.37 13.39 6.56 14.21 7.11 14.77L9.24 16.89C9.79 17.45 10.61 17.63 11.36 17.35C13.5 16.53 15.88 15.19 18.07 13C23.73 7.34 21.61 2.39 21.61 2.39M14.54 9.46C13.76 8.68 13.76 7.41 14.54 6.63S16.59 5.85 17.37 6.63C18.14 7.41 18.15 8.68 17.37 9.46C16.59 10.24 15.32 10.24 14.54 9.46M8.88 16.53L7.47 15.12L8.88 16.53M6.24 22L9.88 18.36C9.54 18.27 9.21 18.12 8.91 17.91L4.83 22H6.24M2 22H3.41L8.18 17.24L6.76 15.83L2 20.59V22M2 19.17L6.09 15.09C5.88 14.79 5.73 14.47 5.64 14.12L2 17.76V19.17Z';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.connections.field_icon', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        config()->set('database.default', 'field_icon');

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
    protected function errors(array $input): array
    {
        try {
            $this->request($input)->validateResolved();
        } catch (ValidationException $exception) {
            return $exception->errors();
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  class-string<FormRequest>  $form
     * @return array{icon: string|null, icon_path: string|null}
     */
    protected function attributes(array $input, string $form = StoreVersionField::class): array
    {
        $request = $this->request($input, $form);
        $request->validateResolved();

        return (fn () => $this->iconAttributes())->call($request);
    }

    public function test_a_strategy_field_keeps_the_picked_icon_and_its_path(): void
    {
        $this->assertSame([
            'icon' => 'mdi:rocket-launch',
            'icon_path' => self::ROCKET_PATH,
        ], $this->attributes(['icon' => 'mdi:rocket-launch', 'icon_path' => self::ROCKET_PATH]));
    }

    public function test_an_edited_strategy_field_keeps_the_picked_icon_and_its_path(): void
    {
        $this->assertSame([
            'icon' => 'mdi:rocket-launch',
            'icon_path' => self::ROCKET_PATH,
        ], $this->attributes(['icon' => 'mdi:rocket-launch', 'icon_path' => self::ROCKET_PATH], UpdateVersionField::class));
    }

    public function test_a_briefing_field_stores_no_icon(): void
    {
        $input = [
            'group_type' => VersionGroupType::BRIEFINGS->value,
            'icon' => 'mdi:rocket-launch',
            'icon_path' => self::ROCKET_PATH,
        ];

        $this->assertSame(['icon' => null, 'icon_path' => null], $this->attributes($input));
        $this->assertSame(['icon' => null, 'icon_path' => null], $this->attributes($input, UpdateVersionField::class));
    }

    public function test_a_removed_icon_drops_its_path(): void
    {
        $this->assertSame(
            ['icon' => null, 'icon_path' => null],
            $this->attributes(['icon' => '', 'icon_path' => self::ROCKET_PATH]),
        );
    }

    public function test_a_form_without_an_icon_stores_none(): void
    {
        $this->assertSame(['icon' => null, 'icon_path' => null], $this->attributes([]));
    }

    public function test_an_icon_needs_its_path(): void
    {
        $this->assertArrayHasKey('icon_path', $this->errors(['icon' => 'mdi:rocket-launch']));
        $this->assertArrayHasKey('icon_path', $this->errors(['icon' => 'mdi:rocket-launch', 'icon_path' => '']));
    }

    public function test_the_icon_must_be_named_as_the_picker_names_it(): void
    {
        foreach (['rocket-launch', 'mdi:', 'mdi:Rocket', 'mdi:rocket launch', 'mdi:-rocket', 'mdi:rocket-', 'fa:rocket'] as $icon) {
            $errors = $this->errors(['icon' => $icon, 'icon_path' => self::ROCKET_PATH]);

            $this->assertArrayHasKey('icon', $errors, $icon);
        }
    }

    public function test_names_with_numbers_are_accepted(): void
    {
        foreach (['mdi:numeric-10-box', 'mdi:signal-3g', 'mdi:alpha-a-box'] as $icon) {
            $errors = $this->errors(['icon' => $icon, 'icon_path' => self::ROCKET_PATH]);

            $this->assertArrayNotHasKey('icon', $errors, $icon);
        }
    }

    public function test_a_path_carrying_markup_is_refused(): void
    {
        foreach (['M0 0"/><script>alert(1)</script>', 'M0 0" onload="alert(1)', 'url(javascript:alert(1))'] as $path) {
            $errors = $this->errors(['icon' => 'mdi:rocket-launch', 'icon_path' => $path]);

            $this->assertArrayHasKey('icon_path', $errors, $path);
        }
    }

    public function test_the_path_length_is_bounded(): void
    {
        $longest = str_pad('M0 0', StoreVersionField::MAX_ICON_PATH_LENGTH, ' 0');

        $this->assertArrayNotHasKey('icon_path', $this->errors(['icon' => 'mdi:rocket-launch', 'icon_path' => $longest]));
        $this->assertArrayHasKey('icon_path', $this->errors(['icon' => 'mdi:rocket-launch', 'icon_path' => $longest.'0']));
    }

    public function test_only_the_strategy_group_supports_an_icon(): void
    {
        foreach (VersionGroupType::cases() as $groupType) {
            $this->assertSame($groupType === VersionGroupType::STRATEGIES, $groupType->supportsIcon(), $groupType->name);
        }
    }

    public function test_a_field_takes_the_icon_and_its_path(): void
    {
        $field = new VersionField(['icon' => 'mdi:rocket-launch', 'icon_path' => self::ROCKET_PATH]);

        $this->assertSame('mdi:rocket-launch', $field->icon);
        $this->assertSame(self::ROCKET_PATH, $field->icon_path);
    }

    public function test_both_field_forms_validate_the_icon(): void
    {
        $this->assertContains(ValidatesIcon::class, class_uses(StoreVersionField::class));
        $this->assertContains(ValidatesIcon::class, class_uses(UpdateVersionField::class));
    }
}
