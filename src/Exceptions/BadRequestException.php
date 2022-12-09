<?php
namespace Plate\PlateFramework\Exceptions;

use Exception;
use Throwable;

class BadRequestException extends Exception
{
    public function __construct(string $message, Throwable $previous = null)
    {
        parent::__construct($message, 400, $previous);
    }
} // HTTP 400