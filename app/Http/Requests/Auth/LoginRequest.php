<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Http\Requests\Auth;

use Mugiew\StarterKit\Http\Requests\FormRequest;
use Mugiew\StarterKit\Http\Requests\Concerns\NormalizesUserInput;

final class LoginRequest extends FormRequest
{
    use NormalizesUserInput;

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
        $this->normalizeEmailField();
    }
}
