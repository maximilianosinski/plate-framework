<?php
namespace Plate\PlateFramework\Exceptions;

use Exception;
use Throwable;

class InternalServerException extends Exception
{
    public function __construct(string $message, Throwable $previous = null)
    {
        parent::__construct($message, 500, $previous);
    }
} // HTTP 500