<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Http\Requests\Validation;

use RuntimeException;

final class RequestValidationException extends RuntimeException
{
    public function __construct(
        private readonly string $redirectPath,
        string $message,
    ) {
        parent::__construct($message);
    }

    public function redirectPath(): string
    {
        return $this->redirectPath;
    }
}
