<?php
namespace Plate\PlateFramework\Exceptions;

use Exception;
use Throwable;

class NotFoundException extends Exception
{
    public function __construct(string $message, Throwable $previous = null)
    {
        parent::__construct($message, 404, $previous);
    }
} // HTTP 404