<?php

namespace JoshWhatK\Eloquent\Extensions;

use Exception;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use JoshWhatK\Eloquent\Extensions\Exceptions\Wrench;
use JoshWhatK\Eloquent\Extensions\Contracts\QueriableContract as Queriable;
use JoshWhatK\Eloquent\Extensions\Exceptions\ColumnMappingNotFoundException;


class Query {

    public $query;
    private $input_query;
    private $initial_queryBuilder;
    private $queryBuilder;
    private $Model;
    private $operations = [
        '~' => 'like',
        '>=' => 'greater-than-equals',
        '<=' => 'less-than-equals',
        '>' => 'greater-than',
        '<' => 'less-than',
        '!=' => 'not-equals',
        '=' => 'equals',
    ];
    private $column_maps;

    public function __construct($input_query, Builder $queryBuilder, Queriable $model)
    {
        $this->input_query = $input_query;
        $this->initial_queryBuilder = $queryBuilder;
        $this->queryBuilder = $queryBuilder;
        $this->Model = $model;
        $this->column_maps = $this->Model->columnMaps();
        $this->boot();
    }

    private function boot()
    {
        $this->query = collect(explode('\,', $this->input_query))
        ->map(function($item)
        {
            return collect(explode('|', $item));
        })
        ->map(function($item)
        {
            return $item->map(function($item)
            {
                foreach($this->operations as $operator => $name)
                {
                    if(str_contains($item, $operator))
                    {
                        $parts = explode($operator, $item);
                        return collect([
                            'name' => $name,
                            'column' => $parts[0],
                            'operator' => $this->handleOperator($operator),
                            'value' => $this->handleValueForOperator($parts[1], $operator),
                        ]);
                    }
                }
            });
        });
    }

    public function query()
    {
        try
        {
            return $this->buildQuery();
        }
        catch(Wrench $e)
        {
            return $this->Model->fullSearch($this->initial_queryBuilder, $this->input_query);
        }
    }

    protected function buildQuery()
    {
        return $this->queryBuilder->where(function($query)
        {
            foreach($this->query as $group)
            {
                //-- handle OR statements
                if($group->count() > 1)
                {
                    $query = $query->where(function($query) use ($group)
                    {
                        foreach ($group as $orGroup)
                        {
                            $query = $this->handleOrWhere($orGroup, $query);
                        }

                        return $query;
                    });
                }
                //-- handle AND statements
                else
                {
                    $query = $this->handleAndWhere($group->first(), $query);
                }
            }

            return $query;
        });
    }

    private function handleOperator($operator)
    {
        if($operator === '~')
        {
            return 'like';
        }

        return $operator;
    }

    private function handleValueForOperator($value, $operator)
    {
        if($operator === '~')
        {
            return '%'.$value.'%';
        }

        return $value;
    }

    private function handleOrWhere($group, $queryBuilder)
    {
        $column = $this->map_column($group['column']);

        if(is_array($column))
        {
            $group['value'] = $this->convertValue($column, $group);

            //-- if it is a relation
            if(array_key_exists('relation', $column))
            {
                return $queryBuilder->orWhereHas($column['relation'], function($query) use ($column, $group)
                {
                    return $query->where($column['column'], $group['operator'], $group['value']);
                });
            }
        }

        return $queryBuilder->orWhere($column['column'], $group['operator'], $group['value']);
    }

    private function handleAndWhere($group, $queryBuilder)
    {
        $column = $this->map_column($group['column']);

        if(is_array($column))
        {
            $group['value'] = $this->convertValue($column, $group);

            //-- if it is a relation
            if(array_key_exists('relation', $column))
            {
                return $queryBuilder->whereHas($column['relation'], function($query) use ($column, $group)
                {
                    return $query->where($column['column'], $group['operator'], $group['value']);
                });
            }
        }

        return $queryBuilder->where($column['column'], $group['operator'], $group['value']);
    }

    private function convertValue($columnMapping, $group)
    {
        //-- if the value needs converted
        if(array_key_exists('convert', $columnMapping))
        {
            if(str_contains($columnMapping['convert'], 'date'))
            {
                $date = new Carbon($group['value']);

                if(str_contains($columnMapping['convert'], 'start'))
                {
                    $date->startOfDay();
                }
                elseif(str_contains($columnMapping['convert'], 'end'))
                {
                    $date->endOfDay();
                }
            }


            return $date;
        }

        return $group['value'];
    }

    public function map_column($column)
    {
        if(array_key_exists($column, $this->column_maps))
        {
            return $this->column_maps[$column];
        }

        throw new ColumnMappingNotFoundException($column);
    }
}