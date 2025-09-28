<?php

namespace App\Exceptions\SleeperApi;

use Exception;

class InsufficientDataException extends Exception
{
    public function __construct(string $message = 'Insufficient data to perform analysis')
    {
        parent::__construct($message);
    }
}
