<?php

declare(strict_types=1);

namespace Rebet\Tests\Http\Response;

use Rebet\Http\Response;
use Rebet\Http\Response\StreamedResponse;
use Rebet\Tests\RebetTestCase;

class StreamedResponseTest extends RebetTestCase
{
    public function test___construct(): void
    {
        $response = new StreamedResponse();
        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertInstanceOf(Response::class, $response);
    }
}
