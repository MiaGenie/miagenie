<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SetTranslationFields extends Command
{
    /**
     * @var string
     */
    protected $signature = 'genie:set-translation-fields  {--model=}';

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

        $model::all()->each(
            function ($record) {
                foreach ($record->translatable as $field) {
                    if (empty($record->{$field})) {
                        $record->{$field} = null;
                    } else {
                        $json = [];
                        $json['en-GB'] = trim($record->{$field}, '"');
                        $jsonString = json_encode($json, JSON_UNESCAPED_SLASHES);
                        $record->{$field} = $jsonString;
                    }
                }
                $record->save();
            }
        );
        $this->info('Done');

    }

}
