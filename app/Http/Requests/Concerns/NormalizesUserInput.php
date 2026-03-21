<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Http\Requests\Concerns;

trait NormalizesUserInput
{
    protected function normalizeEmailField(string $field = 'email'): void
    {
        $this->merge([
            $field => strtolower(trim((string) $this->input($field, ''))),
        ]);
    }

    protected function normalizeUserCoreFields(): void
    {
        $this->merge([
            'username' => strtolower(trim((string) $this->input('username', ''))),
            'name' => trim((string) $this->input('name', '')),
            'email' => strtolower(trim((string) $this->input('email', ''))),
        ]);
    }

    protected function normalizePasswordFields(
        string $passwordField = 'password',
        string $confirmationField = 'password_confirmation'
    ): void {
        $this->merge([
            $passwordField => (string) $this->input($passwordField, ''),
            $confirmationField => (string) $this->input($confirmationField, ''),
        ]);
    }

    protected function normalizeIntegerField(string $field, int $default = 0): void
    {
        $this->merge([
            $field => $this->input($field, $default),
        ]);
    }
}
