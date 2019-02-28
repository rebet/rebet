<?php
namespace Rebet\Tests\Http\Middleware;

use Rebet\Http\Middleware\EmptyStringToNull;
use Rebet\Http\Responder;
use Rebet\Http\Response\BasicResponse;
use Rebet\Tests\RebetTestCase;

class EmptyStringToNullTest extends RebetTestCase
{
    public function test___construct()
    {
        $this->assertInstanceOf(EmptyStringToNull::class, new EmptyStringToNull());
    }

    public function test_handle()
    {
        $middleware  = new EmptyStringToNull();
        $destination = function ($request) { return Responder::toResponse('OK'); };

        $request  = $this->createRequestMock('/');
        $request->query->add([
            'q_null'         => null,
            'q_empty_string' => '',
            'q_empty_array'  => [],
            'q_number_zero'  => 0,
            'q_number'       => 1,
            'q_array'        => [1, '', 3, null, 5],
            'q_not_empty'    => 'a',
            'q_map'          => [
                'q_null'         => null,
                'q_empty_string' => '',
                'q_empty_array'  => [],
                'q_number_zero'  => 0,
                'q_number'       => 1,
                'q_array'        => [1, '', 3, null, 5],
                'q_not_empty'    => 'a',
            ]
        ]);
        $request->request->add([
            'r_null'         => null,
            'r_empty_string' => '',
            'r_empty_array'  => [],
            'r_number_zero'  => 0,
            'r_number'       => 1,
            'r_array'        => [1, '', 3, null, 5],
            'r_not_empty'    => 'a',
            'r_map'          => [
                'r_null'         => null,
                'r_empty_string' => '',
                'r_empty_array'  => [],
                'r_number_zero'  => 0,
                'r_number'       => 1,
                'r_array'        => [1, '', 3, null, 5],
                'r_not_empty'    => 'a',
            ]
        ]);

        $response = $middleware->handle($request, $destination);
        $this->assertInstanceOf(BasicResponse::class, $response);
        $this->assertSame('OK', $response->getContent());
        $this->assertSame([
            'r_null'         => null,
            'r_empty_string' => null,
            'r_empty_array'  => [],
            'r_number_zero'  => 0,
            'r_number'       => 1,
            'r_array'        => [1, null, 3, null, 5],
            'r_not_empty'    => 'a',
            'r_map'          => [
                'r_null'         => null,
                'r_empty_string' => null,
                'r_empty_array'  => [],
                'r_number_zero'  => 0,
                'r_number'       => 1,
                'r_array'        => [1, null, 3, null, 5],
                'r_not_empty'    => 'a',
            ],
            'q_null'         => null,
            'q_empty_string' => null,
            'q_empty_array'  => [],
            'q_number_zero'  => 0,
            'q_number'       => 1,
            'q_array'        => [1, null, 3, null, 5],
            'q_not_empty'    => 'a',
            'q_map'          => [
                'q_null'         => null,
                'q_empty_string' => null,
                'q_empty_array'  => [],
                'q_number_zero'  => 0,
                'q_number'       => 1,
                'q_array'        => [1, null, 3, null, 5],
                'q_not_empty'    => 'a',
            ],
        ], $request->input());
    }
}
