<?php

namespace Bythepixel\Validator;

use Bytepath\Shared\Validator\ValidationResult\FailedValidation as BaseFailedValidation;
use Illuminate\Contracts\Support\Responsable;

/**
 * A class that holds a list of validation errors.
 */
class FailedValidation extends BaseFailedValidation implements Responsable
{
    /**
     * Converts this object to a Laravel Response if returned from a controller method
     */
    public function toResponse($request)
    {
        return response()->json([
            "errors" => $this->getErrors(),
        ], 422);
    }
}
