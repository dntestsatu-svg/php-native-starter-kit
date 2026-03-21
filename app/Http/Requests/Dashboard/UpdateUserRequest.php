<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Http\Requests\Dashboard;

use Mugiew\StarterKit\Http\Requests\Concerns\NormalizesUserInput;
use Mugiew\StarterKit\Http\Requests\FormRequest;
use Mugiew\StarterKit\Http\Requests\Validation\UserValidationRules;

final class UpdateUserRequest extends FormRequest
{
    use NormalizesUserInput;

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
        $this->normalizeIntegerField('user_id', 0);
        $this->normalizeUserCoreFields();
        $this->normalizePasswordFields();
    }

    protected function redirectPathFromInput(): string
    {
        return '/dashboard/users/edit?user_id=' . (int) $this->input('user_id', 0);
    }
}
