<?php

declare(strict_types=1);

namespace TestApp\Validation;

use Override;
use Rebet\Validation\Rule;
use Rebet\Validation\Valid;

class BarValidation extends Rule
{
    #[Override]
    public function rules(): array
    {
        return [
            'bar' => [
                'rule' => [
                    ['C', Valid::REQUIRED],
                ],
            ],
        ];
    }
}
