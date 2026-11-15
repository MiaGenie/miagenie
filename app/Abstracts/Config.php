<?php

namespace App\Abstracts;

use App\Contracts\Config as ConfigContract;
use App\Models\Config as ConfigModel;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config as ConfigApp;
use Illuminate\Support\Facades\Crypt;

abstract class Config implements ConfigContract
{
    public function __construct(public readonly ?Request $request = null) {}

    public function save(array $data = []): void
    {
        foreach ($this->form() as $name => $_) {
            $payload = Arr::get($data, $name, $this->request->input($name));

            if ($payload && $this->encrypted()[$name]) {
                $payload = Crypt::encrypt($payload);
            }

            $this->persistData($name, $payload);
        }
    }

    public function persistData(string $name, mixed $payload): void
    {
        $this->insert($name, $payload);
        $this->putCache($name, $payload);
    }

    public function insert(string $name, mixed $payload): void
    {
        ConfigModel::updateOrCreate(
            ['name' => $name, 'group' => $this->group()],
            ['payload' => $payload]
        );
    }

    /**
     * @return false|mixed
     */
    public function get(string $name)
    {
        $payload = $this->getCache($name, function () use ($name) {
            $payload = ConfigModel::get(
                property: "{$this->group()}.$name",
                default: Arr::get($this->form(), $name)
            );

            $this->putCache($name, $payload);

            return $payload;
        });

        if ($payload && $this->encrypted()[$name]) {
            $payload = Crypt::decrypt($payload);
        }

        return $payload;
    }

    public function all(): array
    {
        return Arr::map($this->form(), function ($_, $name) {
            return $this->get($name);
        });
    }

    public function putCache(string $name, mixed $default = null): void
    {
        Cache::put($this->resolveCacheKey($name), $default);
    }

    /**
     * @return mixed
     */
    public function getCache(string $name, mixed $default = null)
    {
        return Cache::get($this->resolveCacheKey($name), $default);
    }

    public function forgetCache(?string $name = null): void
    {
        if (! $name) {
            foreach ($this->all() as $name) {
                $this->forgetCache($name);
            }

            return;
        }

        Cache::forget($this->resolveCacheKey($name));
    }

    private function resolveCacheKey(string $key): string
    {
        return ConfigApp::get('genie.cache_prefix').".configs.{$this->group()}.$key";
    }
}
