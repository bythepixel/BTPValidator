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
use \Closure;

abstract class ValidationResult
{
    /**
     * Returns true if validation passed, false if failed
     * @return bool
     */
    abstract public function passes(): bool;

    /**
     * Returns a key val list of errors and a message explaining the problem
     * @return array
     */
    public function getErrors(): array
    {
        return [];
    }

    /**
     * Returns a key val list of data returned from validation
     * @return array|null|mixed an array by default, but can be anything
     */
    public function getData(): mixed
    {
        return [];
    }

    /**
     * Allows you to transform the data returned by this function if successful
     * @return $this
     */
    public function transform(Closure $callback): self
    {
        return $this;
    }

    /**
     * Returns the error message for the provided error
     * @param string $name a key in the list of errors
     * @throws ValidationErrorDoesNotExistException
     */
    public function getError($name): string
    {
        throw ValidationErrorDoesNotExistException::forName($name);
    }
}
