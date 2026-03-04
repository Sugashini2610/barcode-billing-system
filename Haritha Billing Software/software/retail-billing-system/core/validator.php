<?php
/**
 * Input Validator - PHP 5.6 compatible
 */
class Validator
{
    private $errors = array();
    private $data = array();

    public function __construct($data)
    {
        $this->data = $data;
    }

    public static function make($data, $rules)
    {
        $instance = new self($data);
        $instance->validate($rules);
        return $instance;
    }

    private function validate($rules)
    {
        foreach ($rules as $field => $ruleString) {
            $value = isset($this->data[$field]) ? $this->data[$field] : null;
            $ruleList = explode('|', $ruleString);
            foreach ($ruleList as $rule) {
                $this->applyRule($field, $value, $rule);
            }
        }
    }

    private function applyRule($field, $value, $rule)
    {
        $label = ucwords(str_replace('_', ' ', $field));

        if ($rule === 'required') {
            if (empty($value) && $value !== '0') {
                $this->errors[$field][] = "$label is required.";
            }
        } elseif (substr($rule, 0, 4) === 'min:') {
            $min = (int) substr($rule, 4);
            if (strlen($value) < $min) {
                $this->errors[$field][] = "$label must be at least $min characters.";
            }
        } elseif (substr($rule, 0, 4) === 'max:') {
            $max = (int) substr($rule, 4);
            if (strlen($value) > $max) {
                $this->errors[$field][] = "$label must not exceed $max characters.";
            }
        } elseif ($rule === 'numeric') {
            if (!is_numeric($value)) {
                $this->errors[$field][] = "$label must be a number.";
            }
        } elseif ($rule === 'email') {
            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $this->errors[$field][] = "$label must be a valid email.";
            }
        } elseif ($rule === 'positive') {
            if (!is_numeric($value) || $value <= 0) {
                $this->errors[$field][] = "$label must be a positive number.";
            }
        } elseif (substr($rule, 0, 3) === 'in:') {
            $allowed = explode(',', substr($rule, 3));
            if (!in_array($value, $allowed)) {
                $this->errors[$field][] = "$label must be one of: " . implode(', ', $allowed);
            }
        }
    }

    public function fails()
    {
        return !empty($this->errors);
    }

    public function errors()
    {
        return $this->errors;
    }
}
