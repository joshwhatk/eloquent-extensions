<?php

namespace JoshWhatK\Eloquent\Extensions\Exceptions;

use Exception;

class Wrench extends Exception
{
    //-- Redefine the exception so message isn't optional
    public function __construct($code = 500, $message = 'Threw a Wrench in it.', Exception $previous = null)
    {
        //-- allow simple overwrites in child classes
        if(count(func_get_args()) === 0)
        {
            if($this->message !== '') $message = $this->message;
            if($this->code !== 0) $code = $this->code;
        }

        //-- make sure everything is assigned properly
        parent::__construct($message, $code, $previous);
    }

    //-- custom string representation
    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}