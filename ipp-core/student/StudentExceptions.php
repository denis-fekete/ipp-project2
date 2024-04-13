<?php

namespace IPP\Student;

use Exception;
use Throwable;

class StudentExceptions extends Exception
{
    /**
     * @param string $message The error message.
     * @param int $code The error code.
     * @param Throwable|null $previous The previous exception, if available.
     */
    public function __construct(string $message = "", int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}