<?php

namespace App\Console\Commands;

use DB;
use Illuminate\Console\Command;

class SetTranslationFieldsPT extends Command
{
    /**
     * @var string
     */
    protected $signature = 'genie:set-translation-fields-pt  {--model=}';

    /**
     * @var string
     */
    protected $description = 'Set Model Translation Fields to valid JSON string using current value as "EN"';

    /**
     * @return void
     */
    public function handle(): void
    {
        if (!$this->option('model')) {
            $this->info('A model is required. Use --model= with correct casing');
            return;
        }
        $modelName = 'App\\Models\\' . $this->option('model');

        try {
            $model = app($modelName);
        } catch (\Throwable $th) {
            $this->info('The supplied model name is not valid. Use --model= with correct casing');
        }

        if (empty($model->translatable)) {
            $this->info("The supplied model name doesn't have translatable fields");
        }

        $ptRecords = DB::table('genie_rule_steps_pt')->get();

        $model::all()->each(
            function ($record) use ($ptRecords) {
                $pt = $ptRecords->first(
                    function ($ptRecord) use ($record) {

                        return $record->uuid === $ptRecord->uuid;

                    }
                );
                foreach ($record->translatable as $field) {
                    $record->setTranslation($field, 'pt-PT', $pt->{$field});
                }
                $record->save();
            }
        );
        $this->info('Done');

    }

}
