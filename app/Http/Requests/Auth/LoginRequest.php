<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Http\Requests\Auth;

use Mugiew\StarterKit\Http\Requests\FormRequest;

final class LoginRequest extends FormRequest
{
    public static function redirectPath(): string
    {
        return '/login';
    }

    /**
     * @return array<string, string>
     */
    protected function rules(): array
    {
        return [
            'email' => 'required|email|max:255',
            'password' => 'required|string|max:255',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower(trim((string) $this->input('email', ''))),
        ]);
    }
}
