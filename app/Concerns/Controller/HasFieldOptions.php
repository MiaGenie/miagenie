<?php

namespace App\Concerns\Controller;

trait HasFieldOptions
{

    /**
     * @param array $fields
     * @return array
     */
    public static function groupFieldOptions(array $fields = []): array
    {
        return array_map(function ($field) {

            $field['options'] = self::groupOptions($field['options']);
            return $field;

        }, $fields);
    }

    /**
     * @param array $options
     * @return array
     */
    public static function groupOptions(array $options = []): array
    {
        return array_reduce($options, function ($result, $option) {

            $result[$option['group']][$option['position']] = $option;
            return $result;

        }, [[]]);
    }
}
