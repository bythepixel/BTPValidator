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

use Bytepath\Shared\Validator\Interfaces\ValidatorInterface;
use Exception;

class ValidationException extends Exception
{
    /**
     * The provided validator could not validate due to illegal rules of some sort
     * @param ValidatorInterface $validator the validator that could not validate
     * @return static
     */
    public static function invalidRules($validator)
    {
        $className = get_class($validator);
        $msg = $className . " has invalid rules";

        // If you end up here check your rules using a debugger.

        return new static($msg);
    }

    /**
     * The provided validator does not have any rules
     * @param ValidatorInterface $validator the validator that could not validate
     * @return static
     */
    public static function emptyRules($validator)
    {
        $className = get_class($validator);
        $msg = $className . " does not have any rules";

        // If you end up here check your rules using a debugger.

        return new static($msg);
    }
}
