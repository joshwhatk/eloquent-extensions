<?php

namespace JoshWhatK\Eloquent\Extensions\Exceptions;

class ColumnMappingNotFoundException extends Wrench
{
    protected $code = 500;

    public function __construct($column)
    {
        $this->message = 'Column Mapping not found for “'.$column.'”.';

        parent::__construct();
    }
}
