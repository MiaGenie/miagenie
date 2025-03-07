<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SetupGitIgnore extends Command
{
    /**
     * @var string
     */
    protected $signature = 'mixpost:setup-gitignore';

    /**
     * @var string
     */
    protected $description = 'Setup the .gitignore';

    /**
     * @return void
     */
    public function handle()
    {
        $path = base_path('.gitignore');

        $content = collect(file(base_path('.gitignore')))
            ->reject(fn (string $line) => Str::startsWith($line, 'composer.lock'))
            ->reject(fn (string $line) => Str::startsWith($line, 'public/vendor/mixpost'))
            ->reject(fn (string $line) => Str::startsWith($line, 'public/vendor/mixpost-enterprise'))
            ->reject(fn (string $line) => Str::startsWith($line, 'public/vendor/horizon'))
            ->reject(fn (string $line) => Str::startsWith($line, 'database/migrations/'))
            ->implode('');

        file_put_contents($path, $content);

        $this->info('.gitignore setup complete!');
    }
}
