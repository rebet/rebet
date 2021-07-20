<?php
namespace App\Validation;

use Rebet\Validation\Rule;
use Rebet\Validation\Valid;

class FooValidation extends Rule
{
    public function rules() : array
    {
        return [
            'foo' => [
                'rule'  => [
                    ['C', Valid::REQUIRED]
                ]
            ],
        ];
    }
}
