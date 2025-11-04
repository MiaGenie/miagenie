<?php

namespace App\Concerns;

use App\Models\Strategy;

trait StrategySchemas
{
    /**
     * @return array
     */
    protected function getStrategySchemas(Strategy $strategy): array
    {
        $locale = $strategy->workspace->locale ?? app()->getLocale();
        $schemas = $strategy->run->rule->ruleSteps->map(function ($step) use ($locale) {
            return $step->getTranslation('json_schema', $locale);
        });

        $schemas = $schemas->reduce(function (array $list, $item) {
            $item = json_decode($item, true);
            $list = array_merge($list, $item['properties']);
            return $list;
        }, []);

        return $schemas;
    }

}
