<?php
/*
 * This file is part of the bytepath/shared package.
 *
 * (c) Andrew Reddin <andrew@bytepath.ca>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bytepath\Shared\Validator\ValidationResult;

use Bytepath\Shared\Validator\Exceptions\ValidationErrorDoesNotExistException;

/**
 * A class that holds a list of validation errors.
 */
class FailedValidation extends ValidationResult
{
    /**
     * @param array $errors a key value stores of attributes with validation errors and a msg explaining the error
     */
    public function __construct(protected array $errors = [])
    {
    }

    /**
     * Returns true if this class passes validation
     */
    public function passes(): bool
    {
        return false;
    }

    /**
     * Returns a key val list of errors and a message explaining the error
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Returns the error message for the provided error
     * @param string $name a key in the list of errors
     * @throws ValidationErrorDoesNotExistException
     */
    public function getError($name): string
    {
        if (! array_key_exists($name, $this->errors)) {
            throw ValidationErrorDoesNotExistException::forName($name);
        }

        return $this->errors[$name];
    }
}
