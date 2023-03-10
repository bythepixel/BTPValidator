<?php
/*
 * This file is part of the bytepath/shared package.
 *
 * (c) Andrew Reddin <andrew@bytepath.ca>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bytepath\Shared\Validator\Tests;

use Bytepath\Shared\Testing\TestCase;
use Bytepath\Shared\Validator\Exceptions\ValidationException;
use Bytepath\Shared\Validator\ValidationResult\FailedValidation;
use Bytepath\Shared\Validator\ValidationResult\PassedValidation;
use Bytepath\Shared\Validator\ValidationResult\ValidationResult;
use Bytepath\Shared\Validator\Validator;

class ValidatorTest extends TestCase
{
    public function test_ActionResult_transform_function_does_not_run_callback_if_validation_fails()
    {
        $failed = $this->getFails()->validate(["cat" => "dog"]);

        $i = 0;
        $returned = $failed->transform(function () use(&$i) {
            $i++;
        });

        $this->assertIsClass(FailedValidation::class, $returned);
        $this->assertEquals(0, $i);
    }

    public function test_ActionResult_transform_function_mutates_data_if_validation_passes()
    {
        $passed = $this->getPasses()->validate(["cat" => "dog"], function() {
            return [
                "hello" => "goodbye",
            ];
        });

        $i = 0;
        $returned = $passed->transform(function ($data) use(&$i) {
            $i++;
            return json_encode($data);
        });

        $this->assertIsClass(PassedValidation::class, $returned);
        $this->assertEquals(1, $i);
        $this->assertEquals('{"hello":"goodbye"}', $passed->getData());
    }


    public function test_passedValidation_closure_only_receives_values_that_have_corresponding_rules()
    {
        // There should only be keys in $validatedData that have a rule func to evaluate
        $rules = [
            "a" => "required",
            "b" => "required",
        ];

        $result = $this->getPasses($rules)->validate([
            "a" => 123,
            "b" => "cats",
            "c" => 345,
        ], function($validatedData) {
            return $validatedData;
        });

        $this->assertTrue(array_key_exists("a", $result->getData()));
        $this->assertEquals(123, $result->getData()["a"]);

        $this->assertTrue(array_key_exists("b", $result->getData()));
        $this->assertEquals("cats", $result->getData()["b"]);

        $this->assertFalse(array_key_exists("c", $result->getData()));
    }

    public function test_validate_returns_a_FailedValidation_if_it_fails()
    {
        $this->assertIsClass(FailedValidation::class, $this->getFails()->validate(["a" => "b"]));
    }

    public function test_validate_returns_a_PassedValidation_if_it_passes()
    {
        $this->assertIsClass(PassedValidation::class, $this->getPasses()->validate(["a" => "b"]));
    }

    public function test_validate_throws_exception_if_list_of_rules_is_empty()
    {
        $this->expectException(ValidationException::class);
        $this->getPasses([])->validate([]);
    }

    public function test_passedValidation_getData_is_empty_array_if_closure_does_not_return_a_value()
    {
        $passed = $this->getPasses()->validate(["a" => "b"], function($validatedData) {});
        $this->assertTrue(is_array($passed->getData()));
        $this->assertTrue(empty($passed->getData()));
    }

    public function test_passedValidation_getData_returns_value_returned_from_closure()
    {
        $passed = $this->getPasses()->validate(["a" => "b"], function($validatedData) {
            return "catfood";
        });
        $this->assertEquals("catfood", $passed->getData());

        $passed = $this->getPasses()->validate(["a" => "b"], function($validatedData) {
            $retval = new \stdClass();
            $retval->cat = "hello";
            $retval->dog = "goodbye";

            return $retval;
        });
        $this->assertIsClass(\stdClass::class, $passed->getData());
        $this->assertEquals("hello", $passed->getData()->cat);
        $this->assertEquals("goodbye", $passed->getData()->dog);
    }

    protected function getPasses($rules = null)
    {
        if($rules === null) {
            $rules = [
                "cat" => "dog",
                "pig" => "horse",
            ];
        }

        return new ValidatorTestValidatorPasses($rules);
    }

    protected function getFails($rules = null)
    {
        if($rules === null) {
            $rules = [
                "cat" => "dog",
                "pig" => "horse",
            ];
        }

        return new ValidatorTestValidatorFails($rules);
    }
}

class ValidatorTestValidatorPasses extends Validator
{
    public function checkData($data, $rules): ValidationResult
    {
        return new PassedValidation();
    }
}

class ValidatorTestValidatorFails extends Validator
{
    public function checkData($data, $rules): ValidationResult
    {
        return new FailedValidation();
    }
}
