<?php
namespace Plate\PlateFramework\Exceptions;

use Exception;
use Throwable;

class ForbiddenException extends Exception
{
    public function __construct(string $message, Throwable $previous = null)
    {
        parent::__construct($message, 403, $previous);
    }
} // HTTP 403