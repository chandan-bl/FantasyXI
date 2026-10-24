<?php

class Validator
{
    /** @var array<string,string> */
    private array $errors = [];

    public function required(array $data, string $field, string $label): static
    {
        if (trim((string)($data[$field] ?? '')) === '') {
            $this->errors[$field] = "$label is required.";
        }
        return $this;
    }

    public function email(array $data, string $field, string $label): static
    {
        $value = trim((string)($data[$field] ?? ''));
        if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "$label must be a valid email address.";
        }
        return $this;
    }

    public function minLength(array $data, string $field, int $length, string $label): static
    {
        $value = (string)($data[$field] ?? '');
        if ($value !== '' && strlen($value) < $length) {
            $this->errors[$field] = "$label must be at least $length characters.";
        }
        return $this;
    }

    public function numeric(array $data, string $field, string $label): static
    {
        $value = $data[$field] ?? '';
        if ($value !== '' && !is_numeric($value)) {
            $this->errors[$field] = "$label must be a number.";
        }
        return $this;
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function first(): ?string
    {
        return $this->errors === [] ? null : array_values($this->errors)[0];
    }
}
