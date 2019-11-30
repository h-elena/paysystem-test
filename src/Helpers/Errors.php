<?php
declare(strict_types=1);

namespace PaySystem\Helpers;

class Errors
{
    /** @var array $errors */
    protected $errors = [];

    /**
     * @param string $error
     */
    public function setError($error)
    {
        $this->errors[] = $error;
    }

    /**
     * Check on error
     *
     * @return bool
     */
    public function hasErrors(): bool
    {
        if (!empty($this->errors)) {
            return true;
        }

        return false;
    }

    /**
     * Get all errors
     *
     * @return string
     */
    public function getErrors(): string
    {
        $result = '';

        if (!empty($this->errors)) {
            if (count($this->errors) > 1) {
                $result = implode(', ', $this->errors);
            } else {
                $result = current($this->errors);
            }
        }

        return $result;
    }
}