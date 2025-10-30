<?php

namespace App\Concerns;

trait GenieSchemaParser
{
    /**
     * @param string $text
     * @param array $replacements
     * @return string
     */
    protected function parseSchemaContent(string $text, array $replacements, array $schema): string
    {
        preg_match_all(
            '/\{\{\{([^}]*)\}\}\}/',
            $text,
            $matches
        );

        $items = [];
        foreach ($matches[1] as $match) {
            $bar = data_get($replacements, $match);
            if (is_array($bar)) {
                ksort($bar);
            }
            $foo = data_get($schema, $match);
            $key = rtrim($match, '.0..9');
            $dar = $this->runResponse->run->rule->version->fields->where(
                'code_name',
                str_replace('strategy.', '', $key)
            )->first();
            $items[$key]['match'] = $match;
            $items[$key]['data'] = $bar;
            $items[$key]['schema'] = $foo;
            $items[$key]['field'] = $dar;
        }

        foreach ($items as $key => $item) {
            if ($item['field']->field_type->name == 'CHECKBOX') {
                $string = implode(', ', array_keys($item['data'], true));
                $text = str_replace('{{{' . $item['match'] . '}}}', $string . "\n", $text);
            } elseif (!is_array($item['data'])) {
                $text = str_replace('{{{' . $item['match'] . '}}}', $item['data'] . "\n", $text);
            } else {
                $string = $this->getStr($item['data'], data_get($schema, $key), $key);
                $text = str_replace('{{{' . $item['match'] . '}}}', $string . "\n", $text);
            }

        }

        return $text;
    }

    /**
     * @param $data
     * @param array $schema
     * @param int|string $key
     * @return string
     */
    public function getStr($data, array $schema, int|string $key): string
    {
        $string = '';
        if (isset($schema['items']) && !isset($schema['items']['type'])) {
            if (isset($schema['description'])) {
                $string .= "\n" . $schema['description'] . "\n";
            }
            $schema = array_shift($schema['items']);
        }
        foreach ($data as $sub_key => $sub_value) {
            if (!is_array($sub_value)) {
                if (!isset($schema['items']) && isset(data_get($schema, $key)['properties'][$sub_key]['description'])) {
                    $string .= "\n" . data_get($schema, $key)['properties'][$sub_key]['description'] . "\n";
                } elseif (isset($schema['properties'][$sub_key]['description'])) {
                    $string .= $schema['properties'][$sub_key]['description'] . "\n";
                }
                $string .= $sub_value . "\n";
            } else {
                if (isset($schema['items'])) {
                    $subschema = $schema['items'];
                } else {
                    $subschema = data_get($schema, $key)['properties'][$sub_key] ?? $schema['properties'][$sub_key] ?? [];
                }
                if (isset($subschema['items'])) {
                    if (isset($subschema['description'])) {
                        $string .= "\n" . $subschema['description'] . "\n";
                    }
                }
                $string .= $this->getStr($sub_value, $subschema, $sub_key);
            }
        }
        return $string;
    }
}
