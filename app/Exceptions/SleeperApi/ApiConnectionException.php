<?php

namespace App\Exceptions\SleeperApi;

use Exception;

class ApiConnectionException extends Exception
{
    public function __construct(string $message = 'Failed to connect to Sleeper API', ?Exception $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
