<?php

namespace JoshWhatK\Eloquent\Extensions\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface QueriableContract
{
    /**
     * Get the column mappings from the Model
     *
     * @return array
     */
    public function columnMaps();

    /**
     * A fallback search method if the mappings fail
     *
     * @param  Illuminate\Database\Eloquent\Builder $query
     * @param  string                               $search   ex. $request->get('query')
     * @return Illuminate\Database\Eloquent\Builder
     */
    public function fullSearch(Builder $query, $search);
}