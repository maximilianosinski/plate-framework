<?php
namespace Plate\PlateFramework\Exceptions;

use Exception;
use Throwable;

class UnauthorizedException extends Exception
{
    public function __construct(string $message, Throwable $previous = null)
    {
        parent::__construct($message, 401, $previous);
    }
} // HTTP 401