<?php

declare(strict_types=1);

namespace Rebet\Tests\Http\Response;

use Rebet\Http\Response;
use Rebet\Http\Response\JsonResponse;
use Rebet\Tests\RebetTestCase;

class JsonResponseTest extends RebetTestCase
{
    public function test___construct(): void
    {
        $response = new JsonResponse();
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertInstanceOf(Response::class, $response);
    }
}
