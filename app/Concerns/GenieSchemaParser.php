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
            $data = data_get($replacements, $match);
            if (is_array($data)) {
                ksort($data);
            }
            $schemaData = data_get($schema, $match);
            $key = rtrim($match, '.0..9');

            $items[$key]['match'] = $match;
            $items[$key]['data'] = $data;
            $items[$key]['schema'] = $schemaData;
        }

        foreach ($items as $key => $item) {
            if (!is_array($item['data'])) {
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
        foreach ($data as $subKey => $subValue) {
            if (!is_array($subValue)) {
                if (is_bool($subValue)) {
                    if ($subValue) {
                        if (isset($schema['properties']) && isset(data_get($schema['properties'], $subKey)['title'])) {
                            $string .= data_get($schema['properties'], $subKey)['title'] . "\n";
                        } else {
                            $string .= $subKey . "\n";
                        }
                    }
                } else {
                    if (!isset($schema['items']) && isset(
                        data_get(
                            $schema,
                            $key
                        )['properties'][$subKey]['title']
                    )) {
                        $string .= "\n" . data_get($schema, $key)['properties'][$subKey]['description'] . "\n";
                    } elseif (isset($schema['properties'][$subKey]['description'])) {
                        $string .= $schema['properties'][$subKey]['description'] . "\n";
                    }
                    $string .= $subValue . "\n";
                }
            } else {
                if (isset($schema['items'])) {
                    $subschema = $schema['items'];
                } else {
                    $subschema = data_get($schema, $key)['properties'][$subKey] ?? $schema['properties'][$subKey] ?? [];
                }
                if (isset($subschema['items'])) {
                    if (isset($subschema['description'])) {
                        $string .= "\n" . $subschema['description'] . "\n";
                    }
                }
                $string .= $this->getStr($subValue, $subschema, $subKey);
            }
        }
        return $string;
    }
}
