<?php
/*
 * This file is part of the bytepath/shared package.
 *
 * (c) Andrew Reddin <andrew@bytepath.ca>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bytepath\Shared\Validator;

/**
 * An implementation of ValidatorInterface that shows how I typically use this interface. Extend this class within your
 * framework, implementing the check() method. this method should do the validation in the native framework way.
 */

use Bytepath\Shared\Validator\ValidationResult\FailedValidation;
use Bytepath\Shared\Validator\ValidationResult\PassedValidation;
use Bytepath\Shared\Validator\ValidationResult\ValidationResult;
use Bytepath\Shared\Validator\Exceptions\ValidationException;
use Bytepath\Shared\Validator\Interfaces\ValidatorInterface;
use Closure;

abstract class Validator implements ValidatorInterface
{
    /**
     * The list of validation rules that data must satisfy
     * @var array
     */
    protected $ruleList = null;

    public function __construct($rules)
    {
        $this->ruleList = $rules;
    }

    /**
     * Returns a new instance of this validator loaded up with the provided $rules
     * @param $rules
     * @return $this
     */
    public function rules($rules): self
    {
        return new static($rules);
    }


    /**
     * Ensures that the list of rules in this validator are ok. If rules fail we throw a ValidationException
     * @param $rules the list of rules in this validator
     * @throws ValidationException
     */
    protected function checkRules($rules)
    {
        if($rules === null) {
            throw ValidationException::invalidRules($this);
        }

        if(empty($rules)) {
            throw ValidationException::emptyRules($this);
        }
    }

    /**
     * Filters any data that does not have a rule we can evaluate against
     * @param $data
     * @param $rules
     */
    protected function filterDataWithoutRules($data, $rules)
    {
        return array_intersect_key($data, $this->ruleList);
    }

    /**
     * Run the provided
     * @return ValidationResult retuns a PassedValidation if OK and a FailedValidation if not OK
     */
    abstract protected function checkData($data, $rules): ValidationResult;

    /**
     * Returns a PassedValidation Object. Extend in your implementation if necessary
     */
    protected function passed($data = []): PassedValidation
    {
        if($data === null) {
            $data = [];
        }

        return new PassedValidation($data);
    }

    /**
     * Returns a FailedValidation Object. Extend in your implementation if necessary
     */
    protected function failed($errors = []): FailedValidation
    {
        if($errors === null) {
            $errors = [];
        }

        return new FailedValidation($errors);
    }

    /**
     * Validate the provided data, and if passes, run the provided closure function.
     *
     * @param array $data
     * @param Closure|null $callback
     * @return ValidationResult
     * @throws ValidationException
     */
    public function validate($data, ?Closure $callback = null): ValidationResult
    {
        // Ensure we have a valid list of rules
        $this->checkRules($this->ruleList);

        // Throw away any data that doesnt have rules
        $filteredData = $this->filterDataWithoutRules($data, $this->ruleList);

        // Validate the provided data
        $validated = $this->checkData($filteredData, $this->ruleList);

        // If validation failed we can just return now
        if(! $validated->passes()) {
            return $validated;
        }

        // If a callback to run on validation success has been provided, do that now. The value returned by the closure
        // should be added to the PassedValidation object we are going to return
        if($callback !== null) {
            return $this->passed($callback($filteredData));
        }

        return $this->passed();
    }
}
