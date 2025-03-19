<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Config extends Model
{

    /**
     * @var string
     */
    protected $table = 'genie_configs';

    /**
     * @var string[]
     */
    protected $fillable = [
        'group',
        'name',
        'payload'
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'payload' => 'array'
    ];

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @param string $key
     * @return string|null
     */
    public static function get(string $property, mixed $default = null)
    {
        [$group, $name] = explode('.', $property);

        $config = self::query()
            ->where('group', $group)
            ->where('name', $name)
            ->first('payload');

        if (!$config) {
            return $default;
        }

        return $config->payload;
    }

    /**
     * @param string $key
     * @param string $value
     * @return Config
     */
    public static function set(string $key, string $value): Config
    {
        return self::updateOrCreate(['key', 'value'], [$key, $value]);
    }
}
