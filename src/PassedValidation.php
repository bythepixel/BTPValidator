<?php

namespace Bythepixel\Validator;

use Bytepath\Shared\Validator\ValidationResult\PassedValidation as BasePassedValidation;
use Illuminate\Contracts\Support\Responsable;

/**
 * A class that holds a list of validation errors.
 */
class PassedValidation extends BasePassedValidation implements Responsable
{
    /**
     * Converts this object into a Laravel Response if returned from a controller method
     */
    public function toResponse($request)
    {
        return response()->json($this->getData());
    }
}
