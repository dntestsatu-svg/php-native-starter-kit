<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Http\Requests\Dashboard;

use Mugiew\StarterKit\Http\Requests\Concerns\NormalizesUserInput;
use Mugiew\StarterKit\Http\Requests\FormRequest;
use Mugiew\StarterKit\Http\Requests\Validation\UserValidationRules;

final class StoreUserRequest extends FormRequest
{
    use NormalizesUserInput;

    public static function redirectPath(): string
    {
        return '/dashboard/users/create';
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
