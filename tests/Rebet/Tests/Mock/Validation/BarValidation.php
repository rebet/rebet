<?php
namespace Rebet\Tests\Mock\Validation;

use Rebet\Validation\Rule;
use Rebet\Validation\Valid;

class BarValidation extends Rule
{
    public function rules() : array
    {
        return [
            'bar' => [
                'rule'  => [
                    ['C', Valid::REQUIRED]
                ]
            ],
        ];
    }
}
