<?php
namespace PlatePHP\PlateFramework\Exceptions;

use Exception;
use Throwable;

class ConflictException extends Exception
{
    public function __construct(string $message, Throwable $previous = null)
    {
        parent::__construct($message, 409, $previous);
    }
} // HTTP 409