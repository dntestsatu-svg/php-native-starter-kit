<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Http\Requests\Validation;

use RuntimeException;

final class ValidationException extends RuntimeException
{
    /**
     * @param array<string, string> $errors
     */
    public function __construct(
        private readonly array $errors,
    ) {
        parent::__construct($this->firstMessage());
    }

    /**
     * @return array<string, string>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    public function firstMessage(): string
    {
        if ($this->errors === []) {
            return 'Validation failed.';
        }

        /** @var array<int, string> $messages */
        $messages = array_values($this->errors);
        return $messages[0];
    }
}
