<?php
// app/Exceptions/InsufficientPointsException.php

namespace App\Exceptions;

use RuntimeException;

class InsufficientPointsException extends RuntimeException
{
    public function __construct(
        public readonly int $requested,
        public readonly int $available,
    ) {
        parent::__construct(
            "Insufficient points: requested {$requested}, available {$available}."
        );
    }
}
