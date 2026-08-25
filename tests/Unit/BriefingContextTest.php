<?php

namespace Tests\Unit;

use App\Enums\FormFieldType;
use App\Genie\Strategy\BriefingContext;
use App\Models\AiRun;
use App\Models\Briefing;
use App\Models\Rule;
use App\Models\Version;
use App\Models\VersionField;
use App\Models\VersionFieldOption;
use Illuminate\Support\Collection;
use Tests\TestCase;

class BriefingContextTest extends TestCase
{
    /**
     * A run with its version's briefing fields and the customer's answers already attached, so the
     * block can be rendered without touching the database.
     *
     * @param  array<int, VersionField>  $fields
     * @param  array<string, mixed>  $content
     */
    protected function runWith(array $fields, array $content): AiRun
    {
        $version = new Version(['name' => 'Test']);
        $version->setRelation('briefings', new Collection($fields));

        $rule = new Rule;
        $rule->setRelation('version', $version);

        $run = new AiRun;
        $run->setRelation('rule', $rule);
        $run->setRelation('briefing', new Briefing(['content' => $content]));

        return $run;
    }

    /**
     * @param  array<string, string>  $options  code_name => label
     */
    protected function field(
        string $codeName,
        string $label,
        FormFieldType $type = FormFieldType::TEXTAREA,
        array $options = []
    ): VersionField {
        $field = new VersionField([
            'code_name' => $codeName,
            'name' => ['en-GB' => $label],
            'field_type' => $type,
        ]);

        $field->setRelation('options', new Collection(
            array_map(
                fn (string $code, string $name) => new VersionFieldOption([
                    'code_name' => $code,
                    'name' => ['en-GB' => $name],
                ]),
                array_keys($options),
                array_values($options),
            )
        ));

        return $field;
    }

    protected function full(array $fields, array $content): string
    {
        return (new BriefingContext($this->runWith($fields, $content), 'en-GB'))->full();
    }

    /**
     * @return array<string, mixed>
     */
    protected function answers(array $fields, array $content): array
    {
        return (new BriefingContext($this->runWith($fields, $content), 'en-GB'))->answers();
    }

    public function test_each_answer_is_a_labelled_block_in_the_order_the_questions_were_asked(): void
    {
        $block = $this->full(
            [$this->field('brand_name', 'Brand Name'), $this->field('location', 'Location(s)')],
            // Stored the other way round: the fields decide the reading order, not the JSON.
            ['location' => 'Porto', 'brand_name' => 'Miagenie'],
        );

        $this->assertSame("**Brand Name**\nMiagenie\n\n**Location(s)**\nPorto", $block);
    }

    public function test_a_list_of_option_codes_becomes_its_labels(): void
    {
        $block = $this->full(
            [$this->field('emotions', 'Evoked Emotions', FormFieldType::CHECKBOX, [
                'confidence' => 'Confidence',
                'excitement' => 'Excitement',
            ])],
            ['emotions' => ['confidence', 'excitement']],
        );

        $this->assertSame("**Evoked Emotions**\nConfidence, Excitement", $block);
    }

    public function test_an_option_code_saved_on_its_own_becomes_its_label(): void
    {
        $block = $this->full(
            [$this->field('niche', 'Niche', FormFieldType::DROP_DOWN, [
                'entertainment_media' => 'Entertainment & Media',
            ])],
            ['niche' => 'entertainment_media'],
        );

        $this->assertSame("**Niche**\nEntertainment & Media", $block);
    }

    public function test_an_option_the_version_no_longer_has_falls_back_to_its_code(): void
    {
        // A briefing outlives the options it was answered with.
        $block = $this->full(
            [$this->field('niche', 'Niche', FormFieldType::DROP_DOWN, ['retail' => 'Retail'])],
            ['niche' => 'hospitality'],
        );

        $this->assertSame("**Niche**\nhospitality", $block);
    }

    public function test_free_text_is_passed_through_untouched(): void
    {
        $story = "Our founder was a freelancer.\r\n\r\nThen she built a platform.";

        $block = $this->full([$this->field('brand_story', 'Brand Story')], ['brand_story' => $story]);

        $this->assertSame("**Brand Story**\n".$story, $block);
    }

    public function test_files_are_left_out_for_now(): void
    {
        $block = $this->full(
            [
                $this->field('logotype', 'Logotype', FormFieldType::FILE),
                $this->field('brand_name', 'Brand Name'),
            ],
            [
                'logotype' => ['id' => 'logo.png', 'path' => 'https://example.test/logo.png'],
                'brand_name' => 'Miagenie',
            ],
        );

        $this->assertSame("**Brand Name**\nMiagenie", $block);
    }

    public function test_a_question_the_customer_skipped_is_left_out(): void
    {
        $block = $this->full(
            [
                $this->field('brand_name', 'Brand Name'),
                $this->field('disliked_brands', 'Disliked Brands'),
                $this->field('goals', 'Goals Online', FormFieldType::CHECKBOX),
                $this->field('never_asked', 'Never Asked'),
            ],
            ['brand_name' => 'Miagenie', 'disliked_brands' => '', 'goals' => []],
        );

        $this->assertSame("**Brand Name**\nMiagenie", $block);
    }

    public function test_answers_are_keyed_by_code_name_with_their_option_labels(): void
    {
        $answers = $this->answers(
            [
                $this->field('brand_name', 'Brand Name'),
                $this->field('niche', 'Niche', FormFieldType::DROP_DOWN, ['retail' => 'Retail']),
                $this->field('emotions', 'Evoked Emotions', FormFieldType::CHECKBOX, [
                    'confidence' => 'Confidence',
                    'excitement' => 'Excitement',
                ]),
            ],
            [
                'brand_name' => 'Miagenie',
                'niche' => 'retail',
                'emotions' => ['confidence', 'excitement'],
            ],
        );

        $this->assertSame([
            'brand_name' => 'Miagenie',
            'niche' => 'Retail',
            'emotions' => 'Confidence, Excitement',
        ], $answers);
    }

    public function test_a_file_answer_is_its_stored_path(): void
    {
        $answers = $this->answers(
            [$this->field('logotype', 'Logotype', FormFieldType::FILE)],
            ['logotype' => ['id' => 'logo.png', 'path' => 'https://example.test/logo.png']],
        );

        $this->assertSame(['logotype' => 'https://example.test/logo.png'], $answers);
    }

    public function test_an_answer_whose_field_is_gone_still_produces_a_replacement(): void
    {
        // A message written against an older briefing keeps resolving: a list has no field left to
        // read its codes against and empties, while text stands on its own.
        $answers = $this->answers([], ['retired_choice' => ['a', 'b'], 'retired_text' => 'Kept']);

        $this->assertSame(['retired_choice' => '', 'retired_text' => 'Kept'], $answers);
    }

    public function test_a_run_without_a_rule_renders_nothing_rather_than_failing(): void
    {
        $run = new AiRun;
        $run->setRelation('rule', null);
        $run->setRelation('briefing', new Briefing(['content' => ['brand_name' => 'Miagenie']]));

        $this->assertSame('', (new BriefingContext($run, 'en-GB'))->full());
    }
}
