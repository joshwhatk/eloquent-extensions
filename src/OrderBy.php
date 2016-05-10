<?php

namespace JoshWhatK\Eloquent\Extensions;

use Illuminate\Database\Eloquent\Builder;
use JoshWhatK\Eloquent\Extensions\Contracts\OrderableContract as Orderable;

class OrderBy
{
    protected $orderBy;
    protected $orderByCollection;
    protected $Model;
    protected $queryBuilder;
    protected $previous_relation = null;

    public static function build(Builder $queryBuilder, $orderBy, Orderable $model)
    {
        $instance = new static($queryBuilder, $orderBy, $model);
        $instance->buildCollection();
        return $instance->orderBy();
    }

    protected function __construct(Builder $queryBuilder, $orderBy, Orderable $model)
    {
        $this->queryBuilder = $queryBuilder;
        $this->orderBy = $orderBy;
        $this->Model = $model;
    }

    protected function buildCollection()
    {
        $this->orderByCollection = collect(explode('|', $this->orderBy))
        ->map(function($item)
        {
            $orderBy = collect(explode(':', $item));
            return collect(['column' => $orderBy[0], 'direction' => $orderBy->get(1, 'asc')]);
        });
    }

    protected function orderBy()
    {
        $this->orderByCollection
        ->each(function($item)
        {
            try
            {
                $column = $this->map_column($item['column']);

                if($this->columnHasRelation($column))
                {
                    $relation = $this->map_order_by($column['relation']);
                    $this->queryBuilder = $this->joinRelation($column);
                    $this->queryBuilder = $this->queryBuilder
                        ->orderBy($relation['as'].'.'.$column['column'], $item['direction']);
                    return;
                }
            }
            catch(ColumnMappingNotFoundException $e){}
            catch(OrderByMappingNotFoundException $e){}

            $this->queryBuilder = $this->queryBuilder->orderBy($item['column'], $item['direction']);
        });

        return $this->queryBuilder;
    }

    protected function joinRelation($column_mapping)
    {
        $relation = $this->map_order_by($column_mapping['relation']);

        if($this->columnHasMultipleRelations($column_mapping))
        {
            $this->previous_relation = null;
            $relations = collect(
                explode('.', $column_mapping['relation'])
            )->map(function($relation)
            {
                if(is_null($this->previous_relation))
                {
                    $this->previous_relation = $relation;
                    return $relation;
                };

                $this->previous_relation .= '.'.$relation;
                return $this->previous_relation;
            })
            ->each(function($relation)
            {
                $mapped_relation = $this->map_order_by($relation);
                $this->queryBuilder = $this->queryBuilder
                ->join(
                    $mapped_relation['table'].' as '.$mapped_relation['as'],
                    $mapped_relation['as'].'.id',
                    '=',
                    $mapped_relation['parent_table'].'.'.$mapped_relation['id']
                );
            });
            $first_relation = $this->map_order_by($relations->first());
            $this->queryBuilder = $this->queryBuilder->select($first_relation['parent_table'].'.*');

            return $this->queryBuilder;
        }

        return $this->queryBuilder
            ->join(
                $relation['table'].' as '.$relation['as'],
                $relation['as'].'.id', '=', $relation['parent_table'].'.'.$relation['id'])
            ->select($relation['parent_table'].'.*');
    }

    protected function map_column($column)
    {
        if(key_exists($column, $this->Model->columnMaps()))
        {
            return $this->Model->columnMaps()[$column];
        }

        throw new ColumnMappingNotFoundException($column);
    }

    protected function map_order_by($relation)
    {
        if(key_exists($relation, $this->Model->orderByMaps()))
        {
            return $this->Model->orderByMaps()[$relation];
        }

        throw new ColumnMappingNotFoundException($relation);
    }

    private function columnHasRelation($column_mapping)
    {
        return key_exists('relation', $column_mapping);
    }

    private function columnHasMultipleRelations($column_mapping)
    {
        return collect(explode('.', $column_mapping['relation']))->count() > 1;
    }
}
