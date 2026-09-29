<?php

declare(strict_types=1);

namespace Rebet\Tests\View\Exception;

use Rebet\Tests\RebetTestCase;
use Rebet\View\Exception\ViewRenderFailedException;

class ViewRenderFailedExceptionTest extends RebetTestCase
{
    public function test___construct(): void
    {
        $e = new ViewRenderFailedException('test');
        $this->assertInstanceOf(ViewRenderFailedException::class, $e);
    }
}
