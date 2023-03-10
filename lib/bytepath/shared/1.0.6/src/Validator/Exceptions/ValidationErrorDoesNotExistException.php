<?php
/*
 * This file is part of the bytepath/shared package.
 *
 * (c) Andrew Reddin <andrew@bytepath.ca>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bytepath\Shared\Validator\Exceptions;

use Exception;

/**
 * Called when attempting to access an error message in the ValidationError class that does not exist
 */
class ValidationErrorDoesNotExistException extends Exception
{
    public static function forName(string $name)
    {
        $msg = $name . " is not a valid validation error";
        return new static($msg);
    }
}
