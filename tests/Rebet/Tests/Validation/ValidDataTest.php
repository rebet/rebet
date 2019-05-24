<?php
namespace Rebet\Tests\Validation;

use Rebet\Tests\RebetTestCase;
use Rebet\Validation\ValidData;

class ValidDataTest extends RebetTestCase
{
    public function test___construct()
    {
        $this->assertInstanceOf(ValidData::class, new ValidData(['id' => 123]));
    }

    public function test___get()
    {
        $data = new ValidData(['id' => 123]);
        $this->assertSame(123, $data->id);
    }

    public function test_get()
    {
        $data = new ValidData(['foo' => ['bar' => 123]]);
        $this->assertSame(123, $data->get('foo.bar'));
    }
}
