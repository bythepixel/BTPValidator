<?php

namespace Bythepixel\Validator;

use Bytepath\Shared\Validator\ValidationResult\ValidationResult;
use Bytepath\Shared\Validator\Validator as BaseValidator;

class BTPValidator extends BaseValidator
{
    public function checkData($data, $rules): ValidationResult
    {
        /**
         * Because this class is abstracted behind ValidatorInterface, it's safe to use the global
         * validator function here even though that would normally be frowned against. The outside
         * world has no idea this is happening making it safe.
         *
         * This function does all the hard work of setting up the Laravel validation library for
         * use outside of a request class which gets pretty messy. It's much simpler to let the
         * IOC container handle it.
         */
        $validator = validator($data, $rules);

        if (! $validator->passes()) {
            return $this->failed($validator->messages()->getMessages());
        }

        return $this->passed();
    }

    protected function passed($data = []): PassedValidation
    {
        return new PassedValidation($data);
    }

    protected function failed($errors = []): FailedValidation
    {
        return new FailedValidation($errors);
    }
}
