<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Http\Requests\Auth;

use Mugiew\StarterKit\Http\Requests\Concerns\NormalizesUserInput;
use Mugiew\StarterKit\Http\Requests\FormRequest;
use Mugiew\StarterKit\Http\Requests\Validation\UserValidationRules;

final class RegisterRequest extends FormRequest
{
    use NormalizesUserInput;

    public static function redirectPath(): string
    {
        return '/register';
    }

    /**
     * @return array<string, string>
     */
    protected function rules(): array
    {
        return UserValidationRules::create();
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeUserCoreFields();
    }
}
