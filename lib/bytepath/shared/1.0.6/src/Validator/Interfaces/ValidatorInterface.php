<?php
/*
 * This file is part of the bytepath/shared package.
 *
 * (c) Andrew Reddin <andrew@bytepath.ca>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bytepath\Shared\Validator\Interfaces;

/**
 * Defines a framework agnostic validation machine that can be used to validate client data before using it in your
 * applications.
 *
 * This machine is immutable meaning any data passed to it should not change the state of the object, instead, a new
 * object with the updated state should be returned.
 */
use Bytepath\Shared\Validator\ValidationResult\ValidationResult;
use Closure;

interface ValidatorInterface
{
    /**
     *  A factory function that returns a new instance of whatever class this is with the internal set of rules replaced
     *  with the provided $rules arg. Currently, it's up to the implementation to decide what $rules should be.
     *  Example
     * If we had a class called ThingValidator that implemented this interface, and we wanted to validate an array of
     * data before we update the database, we would use it like this
     * $validator = $instantiatedThingValidator->rules($rules);
     *
     * @param mixed $rules currently it's up to the implementation to decide what $rules is. This library was written
     *               to wrap around Laravels validation system so that might be a good starting point. Laravel uses
     *               an array of key/vals that define what is and is not valid data. More info in the link below
     *               (https://laravel.com/docs/10.x/validation#available-validation-rules)
     *               In the future, a more fleshed out rules system will be made that
     *               makes rule definition more framework agnostic.
     *
     * @return self Returns a new instance of this validator with the provided rules. If this was ThingValidator, we
     *               would return a new ThingValidator.
     */
    public function rules($rules): self;


    /**
     * Validate the provided data against the internal rules.
     * If the data passes validation,
     *     we run the provided closure, passing it the array of validated data. You can use this closure to perform
     *     whatever action you required validated data to achieve. For example, in this closure you could save the
     *     validated data to the database. the $validated data should only contain data that has been compared
     *     against the internal list of rules. If a key/val set of data has been provided that does not have a
     *     corresponding rule, this data should be ignored and not be present in the closure.
     *
     *     Example
     *     Imagine We have the following rules
     *         name: string, max size 100 characters.
     *         age:  number: max age 100.
     *
     *     And that the data provided to this function is the following
     *         name: "Andrew"
     *         age: 16,
     *         height: 8ft 6in
     *
     *     Because height does not have a rule within this validator, we ignore it and do not
     *     validate against it. We also do not provide it to the list of validated variables
     *
     *     $result = $validator->validate($data, function($validatedData) {
     *         $name = $validatedData["name"];
     *         $age = $validatedData["age"\;
     *         // Height is not present here
     *     });
     *
     *     A PassedValidation object will be returned bt the validate() function.
     *     Any values returned by the closure can be retrieved using the
     *     getData method of the PassedValidationObject
     *
     * If data does not pass validation
     *     A FailedValidation object will be returned. The list of errors that have occurred can be retrieved
     *     using the getErrors() function of the validation object
     *
     *     Example
     *     Imagine We have the following rules
     *         name: string
     *         age:  number: max age 100.
     *
     *     And that the data provided to this function is the following
     *         name: 12345
     *         age: 176,
     *
     *    the validate function will fail on this data and return a ValidationFailed object. This object will
     *    provide a key/val list of errors when you call the getErrors() method that will look something like
     *    this.
     *    [
     *     "name" => "must be string",
     *     "age" => "max age 100 (176 provided)"
     *    ]
     *
     *    Validation rules are provided by the implementation so they may differ depending on your underlying
     *    framework.
     *
     * @param array $data
     * @param Closure|null $callback
     * @return ValidationResult
     */
    public function validate($data, ?Closure $callback): ValidationResult;
}
