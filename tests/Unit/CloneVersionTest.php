<?php

namespace Tests\Unit;

use App\Http\Requests\Admin\CloneVersion;
use App\Models\RuleStep;
use Tests\TestCase;

class CloneVersionTest extends TestCase
{
    /**
     * The dependency remap only reads the id maps, so it can be exercised with hand-filled ones.
     *
     * @param  array<int, int>  $fields
     * @param  array<int, int>  $subFields
     */
    protected function request(array $fields, array $subFields): CloneVersion
    {
        return new class($fields, $subFields) extends CloneVersion
        {
            /**
             * @param  array<int, int>  $fields
             * @param  array<int, int>  $subFields
             */
            public function __construct(array $fields, array $subFields)
            {
                parent::__construct();

                $this->fieldIdMap = $fields;
                $this->subFieldIdMap = $subFields;
            }

            /**
             * @return array{0: int|null, 1: int|null}
             */
            public function remap(RuleStep $step): array
            {
                return $this->remapDependency($step);
            }
        };
    }

    protected function step(int|string|null $field, int|string|null $option): RuleStep
    {
        $step = new RuleStep;
        $step->depends_on_field = $field;
        $step->depends_on_option = $option;

        return $step;
    }

    public function test_dependencies_follow_the_cloned_ids(): void
    {
        $request = $this->request([10 => 20], [100 => 200]);

        $this->assertSame([20, 200], $request->remap($this->step(10, 100)));
        $this->assertSame([20, null], $request->remap($this->step(10, null)));
    }

    public function test_a_step_without_a_dependency_stays_without_one(): void
    {
        $this->assertSame([null, null], $this->request([10 => 20], [])->remap($this->step(null, null)));
    }

    public function test_a_dependency_outside_the_version_is_dropped_rather_than_kept_on_the_source(): void
    {
        $request = $this->request([10 => 20], [100 => 200]);

        $this->assertSame([null, null], $request->remap($this->step(99, 100)));
        $this->assertSame([20, null], $request->remap($this->step(10, 999)));
    }

    public function test_ids_handed_back_as_strings_still_match(): void
    {
        $request = $this->request([10 => 20], [100 => 200]);

        $this->assertSame([20, 200], $request->remap($this->step('10', '100')));
    }
}
