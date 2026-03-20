<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Http\Requests\Dashboard;

use Mugiew\StarterKit\Http\Requests\FormRequest;

final class DeleteUserRequest extends FormRequest
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
        return [
            'user_id' => 'required|integer|min:1',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'user_id' => $this->input('user_id', 0),
        ]);
    }
}
