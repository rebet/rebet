<?php

declare(strict_types=1);

namespace TestApp\Validation;

use Override;
use Rebet\Validation\Rule;
use Rebet\Validation\Valid;

class FooValidation extends Rule
{
    #[Override]
    public function rules(): array
    {
        return [
            'foo' => [
                'rule' => [
                    ['C', Valid::REQUIRED],
                ],
            ],
        ];
    }
}
