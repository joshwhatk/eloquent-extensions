<?php

namespace JoshWhatK\Eloquent\Extensions\Contracts;

interface OrderableContract
{
    /**
     * Get the column mappings from the Model
     *
     * @return array
     */
    public function columnMaps();

    /**
     * Get the OrderBy mappings from the Model
     *
     * @return array
     */
    public function orderByMaps();
}
