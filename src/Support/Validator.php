<?php
namespace App\Support;

class Validator
{
    private array $errors = [];

    public function __construct(private array $data) {}

    public function required(string $field, string $label = null): self
    {
        $label = $label ?? $field;
        if (!isset($this->data[$field]) || trim((string) $this->data[$field]) === '') {
            $this->errors[$field] = "{$label} is required.";
        }
        return $this;
    }

    public function email(string $field): self
    {
        if (!empty($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = 'Invalid email address.';
        }
        return $this;
    }

    public function max(string $field, int $len): self
    {
        if (!empty($this->data[$field]) && mb_strlen((string) $this->data[$field]) > $len) {
            $this->errors[$field] = "May not be longer than {$len} characters.";
        }
        return $this;
    }

    public function min(string $field, int $len): self
    {
        if (isset($this->data[$field]) && mb_strlen((string) $this->data[$field]) < $len) {
            $this->errors[$field] = "Must be at least {$len} characters.";
        }
        return $this;
    }

    public function date(string $field): self
    {
        if (empty($this->data[$field])) return $this;
        $d = \DateTime::createFromFormat('Y-m-d', $this->data[$field]);
        if (!$d || $d->format('Y-m-d') !== $this->data[$field]) {
            $this->errors[$field] = 'Must be a valid date (YYYY-MM-DD).';
        }
        return $this;
    }

    public function addError(string $field, string $message): self
    {
        $this->errors[$field] = $message;
        return $this;
    }

    public function fails(): bool { return $this->errors !== []; }
    public function errors(): array { return $this->errors; }
}