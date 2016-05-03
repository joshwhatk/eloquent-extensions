<?php

namespace JoshWhatK\Eloquent\Extensions\Exceptions;

class OrderByMappingNotFoundException extends Wrench
{
    protected $code = 500;

    public function __construct($relation)
    {
        $this->message = 'OrderBy Mapping not found for “'.$relation.'”.';

        parent::__construct();
    }
}
