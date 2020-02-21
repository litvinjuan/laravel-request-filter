<?php

namespace App\Http\Requests;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;

class RequestFilter
{
    const TYPE_ILIKE = 'type-ilike';
    const TYPE_GROUP = 'type-group';
    const TYPE_LIKE = 'type-like';
    const TYPE_EQUALS = 'type-equals';
    const TYPE_GREATER = 'type-grater';
    const TYPE_GREATER_EQUAL = 'type-grater-equal';
    const TYPE_LOWER = 'type-lower';
    const TYPE_LOWER_EQUAL = 'type-lower-equal';
    const TYPE_DIFFERENT = 'type-different';

    const SOURCE_REQUEST = 'source-request';
    const SOURCE_VALUE = 'source-value';

    /**
     * @param Builder|Relation $builder
     * @param Request $request
     * @param array $filters
     * @return Builder|Relation
     */
    public static function filter($builder, Request $request, array $filters)
    {
        foreach ($filters as $filter) {
            if (isset($filter['unless']) && $filter['unless']($request)) {
                continue;
            }

            $value = static::getValue($filter, $request);

            if (! $value) {
                continue;
            }

            switch ($filter['type']) {
                case static::TYPE_GROUP:
                    $builder->where(function (Builder $builder) use ($request, $filter) {
                        static::filter($builder, $request, $filter['children']);
                    }, null, null, $filter['boolean']);
                    break;
                case static::TYPE_ILIKE:
                    $builder->where($filter['column'], 'ILIKE', "%$value%", $filter['boolean']);
                    break;
                case static::TYPE_EQUALS:
                    $builder->where($filter['column'], '=', $value, $filter['boolean']);
                    break;
                case static::TYPE_GREATER:
                    $builder->where($filter['column'], '>', $value, $filter['boolean']);
                    break;
            }
        }

        return $builder;
    }

    private static function getValue($filter, Request $request)
    {
        if ($filter['source'] == static::SOURCE_REQUEST) {
            if (! isset($filter['key'])) {
                throw new \InvalidArgumentException('We couldn\'t find the key for your filter');
            }
            return $request->get($filter['key']);
        }

        if ($filter['source'] == static::SOURCE_VALUE) {
            if (! isset($filter['value'])) {
                throw new \InvalidArgumentException('We couldn\'t find the key for your filter');
            }
            return $filter['value'];
        }
    }

}
