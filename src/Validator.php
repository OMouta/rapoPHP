<?php

namespace Rapo;

class Validator {
    protected $data;
    protected $errors = [];

    public function __construct(array $data) {
        $this->data = $data;
    }

    public static function make(array $data, array $rules) {
        $validator = new self($data);
        foreach ($rules as $field => $fieldRules) {
            $rulesArray = is_array($fieldRules) ? $fieldRules : explode('|', $fieldRules);
            foreach ($rulesArray as $rule) {
                $validator->applyRule($field, $rule);
            }
        }
        return $validator;
    }

    protected function applyRule($field, $rule) {
        $params = [];
        if (str_contains($rule, ':')) {
            [$rule, $paramStr] = explode(':', $rule);
            $params = explode(',', $paramStr);
        }

        $value = $this->data[$field] ?? null;

        switch ($rule) {
            case 'required':
                if (empty($value) && $value !== '0' && $value !== 0) $this->addError($field, "The $field field is required.");
                break;
            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) $this->addError($field, "The $field must be a valid email address.");
                break;
            case 'min':
                if (strlen((string)$value) < $params[0]) $this->addError($field, "The $field must be at least {$params[0]} characters.");
                break;
            case 'max':
                if (strlen((string)$value) > $params[0]) $this->addError($field, "The $field must not exceed {$params[0]} characters.");
                break;
        }
    }

    protected function addError($field, $message) {
        $this->errors[$field][] = $message;
    }

    public function fails() {
        return !empty($this->errors);
    }

    public function errors() {
        return $this->errors;
    }
}
