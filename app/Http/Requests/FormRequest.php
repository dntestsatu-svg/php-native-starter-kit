<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Http\Requests;

use Mugiew\StarterKit\Core\Request;
use Mugiew\StarterKit\Http\Requests\Validation\RequestValidationException;
use Mugiew\StarterKit\Http\Requests\Validation\ValidationException;
use Mugiew\StarterKit\Http\Requests\Validation\Validator;

abstract class FormRequest
{
    /**
     * @var array<string, mixed>
     */
    protected array $input;

    /**
     * @var array<string, mixed>
     */
    private array $validated = [];

    /**
     * @param array<string, mixed> $input
     */
    final public function __construct(array $input)
    {
        $this->input = $input;
    }

    final public static function fromRequest(Request $request): static
    {
        $instance = new static($request->all());
        $instance->prepareForValidation();

        try {
            $validated = Validator::validate($instance->input, $instance->rules());
            $instance->validated = $instance->transformValidated($validated);
        } catch (ValidationException $exception) {
            throw new RequestValidationException(
                $instance->redirectPathFromInput(),
                $exception->firstMessage()
            );
        }

        return $instance;
    }

    /**
     * @return array<string, mixed>
     */
    final public function validated(): array
    {
        return $this->validated;
    }

    /**
     * @return array<string, string|array<int, string>>
     */
    abstract protected function rules(): array;

    abstract public static function redirectPath(): string;

    protected function redirectPathFromInput(): string
    {
        return static::redirectPath();
    }

    protected function prepareForValidation(): void
    {
        // Intended for request-specific normalization.
    }

    /**
     * @param array<string, mixed> $validated
     * @return array<string, mixed>
     */
    protected function transformValidated(array $validated): array
    {
        return $validated;
    }

    protected function merge(array $data): void
    {
        $this->input = array_merge($this->input, $data);
    }

    protected function input(string $key, mixed $default = null): mixed
    {
        return array_key_exists($key, $this->input) ? $this->input[$key] : $default;
    }
}
