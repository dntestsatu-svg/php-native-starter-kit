<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Http\Requests\Validation;

final class UserValidationRules
{
    /**
     * @return array<string, string>
     */
    public static function create(): array
    {
        return [
            'username' => 'required|string|alpha_num|min:3|max:20',
            'name' => 'required|string|min:3|max:120',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8|confirmed',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function update(): array
    {
        return [
            'user_id' => 'required|integer|min:1',
            'username' => 'required|string|alpha_num|min:3|max:20',
            'name' => 'required|string|min:3|max:120',
            'email' => 'required|email|max:255',
            'password' => 'nullable|string|min:8|confirmed',
        ];
    }
}
