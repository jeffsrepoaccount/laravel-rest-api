<?php namespace Jnet\Api\Validators;

use Illuminate\Validation\Validator;

abstract class ValidatorAbstract extends Validator
{
    public function __construct(array $data)
    {
        parent::__construct(
            app()->make('Symfony\Component\Translation\TranslatorInterface'),
            $data,
            $this->rules,
            $this->messages
        );
    }
}
