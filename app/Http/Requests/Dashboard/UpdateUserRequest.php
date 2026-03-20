<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Http\Requests\Dashboard;

use Mugiew\StarterKit\Http\Requests\FormRequest;
use Mugiew\StarterKit\Http\Requests\Validation\UserValidationRules;

final class UpdateUserRequest extends FormRequest
{
    public static function redirectPath(): string
    {
        return '/dashboard';
    }

    /**
     * @return array<string, string>
     */
    protected function rules(): array
    {
        return UserValidationRules::update();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'user_id' => $this->input('user_id', 0),
            'username' => strtolower(trim((string) $this->input('username', ''))),
            'name' => trim((string) $this->input('name', '')),
            'email' => strtolower(trim((string) $this->input('email', ''))),
            'password' => (string) $this->input('password', ''),
            'password_confirmation' => (string) $this->input('password_confirmation', ''),
        ]);
    }

    protected function redirectPathFromInput(): string
    {
        return '/dashboard/users/edit?user_id=' . (int) $this->input('user_id', 0);
    }
}
