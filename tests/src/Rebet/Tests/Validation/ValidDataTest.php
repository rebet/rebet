<?php

declare(strict_types=1);

namespace Rebet\Tests\Validation;

use Rebet\Tests\RebetTestCase;
use Rebet\Validation\ValidData;

class ValidDataTest extends RebetTestCase
{
    public function test___construct(): void
    {
        $this->assertInstanceOf(ValidData::class, new ValidData(['id' => 123]));
    }

    public function test___get(): void
    {
        $data = new ValidData(['id' => 123]);
        $this->assertSame(123, $data->id);
    }

    public function test_get(): void
    {
        $data = new ValidData(['foo' => ['bar' => 123]]);
        $this->assertSame(123, $data->get('foo.bar'));
    }
}
