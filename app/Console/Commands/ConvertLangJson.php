<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Inovector\Mixpost\Util;

class ConvertLangJson extends Command
{
    /**
     * @var string
     */
    protected $signature = 'genie:convert-lang-json';

    /**
     * @var string
     */
    protected $description = 'Convert language files to json files';

    /**
     * @var array|string[]
     */
    protected array $skipFiles = ['backend.php', 'mail.php'];
    /**
     * @var array|string[]
     */
    protected array $skipKeys = ['backend'];

    /**
     * @var array
     */
    protected array $jsonArray = [];

    /**
     * @return void
     */
    public function handle(): void
    {
        if (App::environment('production')) {
            $this->warn('The application is in production environment.');

            if (!$this->confirm('Do you wish to continue?')) {
                $this->error('Conversion has been cancelled.');
                return;
            }
        }

        $this->createJsonArray();
        $this->createJsonFiles();

        $this->info('All language files have been converted to json files!');
    }

    /**
     * @param string $locale
     * @return array
     */
    protected function getLanguageFiles(string $locale): array
    {
        $files = array_values(
            Arr::where(File::files("{$this->langFolderPath()}/$locale"), function ($file) {
                return !in_array($file->getFilename(), $this->skipFiles);
            })
        );

        $array = [];

        foreach ($files as $file) {
            $jsons = collect(Util::config('locales'))->map(function ($lang) {
                return $lang['long'] . '.json';
            })->toArray();

            if (!in_array($file->getFilename(), $jsons)) {
                $array[] = Str::before($file->getFilename(), '.php');
            }
        }

        return $array;
    }

    /**
     * @return void
     */
    protected function createJsonArray(): void
    {
        foreach (Util::config('locales') as $locale) {
            foreach ($this->getLanguageFiles($locale['long']) as $group) {
                $keys = $this->readLanguageFileAndGetData($locale['long'], $group);

                $onlyFilledKeys = collect($keys)->filter(function ($value) {
                    return $value;
                })->toArray();

                if (count($onlyFilledKeys)) {
                    if (!Str::endsWith($group, '_back')) {
                        $this->jsonArray[$locale['long']][$group] = $onlyFilledKeys;
                    }
                }
            }
        }
    }

    /**
     * @param string $language
     * @param string $fileName
     * @return mixed|object
     */
    public function readLanguageFileAndGetData(string $language, string $fileName): mixed
    {
        $file = "{$this->langFolderPath()}/$language/$fileName.php";

        if (File::exists($file)) {
            return require $file;
        }

        return (object)[];
    }

    /**
     * @return void
     */
    protected function createJsonFiles(): void
    {
        foreach ($this->jsonArray as $language => $contain) {
            $path = "{$this->langFolderJsonPath()}/$language.json";

            if (!file_exists(dirname($path))) {
                mkdir(dirname($path), 0775, true);
            }

            $output = json_encode($this->skipKeys($this->adjustArray($contain), $this->skipKeys), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            file_put_contents($path, $output);
        }
    }

    /**
     * @param array $arr
     * @return array
     */
    protected function adjustArray(array $arr): array
    {
        $res = [];

        foreach ($arr as $key => $val) {
            $key = $this->removeEscapeCharacter($this->adjustString($key));

            if (is_array($val)) {
                $res[$key] = $this->adjustArray($val);
            } else {
                $res[$key] = $this->removeEscapeCharacter($this->adjustString($val));
            }
        }

        return $res;
    }

    /**
     * @param $s
     * @return string
     */
    protected function removeEscapeCharacter($s): string
    {
        $escapedEscapeChar = preg_quote('!', '/');

        return preg_replace_callback(
            "/$escapedEscapeChar(:\w+)/",
            function ($matches) {
                return mb_substr($matches[0], 1);
            },
            $s
        );
    }

    /**
     * @param $s
     * @return string
     */
    protected function adjustString($s): string
    {
        if (!is_string($s)) {
            return $s;
        }

        $escapedEscapeChar = preg_quote('!', '/');

        return preg_replace_callback(
            "/(?<!mailto|tel|$escapedEscapeChar):\w+/",
            function ($matches) {
                return '{' . mb_substr($matches[0], 1) . '}';
            },
            $s
        );
    }

    /**
     * @param array $data
     * @param array $keysToSkip
     * @return array
     */
    protected function skipKeys(array $data, array $keysToSkip): array
    {
        foreach ($data as $key => $value) {
            if (in_array($key, $keysToSkip, true)) {
                unset($data[$key]);
            } elseif (is_array($value)) {
                $data[$key] = $this->skipKeys($value, $keysToSkip);
            }
        }

        return $data;
    }

    /**
     * @return string
     */
    protected function langFolderPath(): string
    {
        return __DIR__ . '/../../../lang';
    }

    /**
     * @return string
     */
    protected function langFolderJsonPath(): string
    {
        return __DIR__ . '/../../../resources/lang-json';
    }
}
