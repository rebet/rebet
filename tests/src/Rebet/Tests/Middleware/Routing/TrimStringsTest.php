<?php
namespace Rebet\Tests\Middleware\Routing;

use Rebet\Http\Responder;
use Rebet\Http\Response\BasicResponse;
use Rebet\Middleware\Routing\TrimStrings;
use Rebet\Tests\RebetTestCase;

class TrimStringsTest extends RebetTestCase
{
    public function test___construct()
    {
        $this->assertInstanceOf(TrimStrings::class, new TrimStrings());
    }

    public function test_handle()
    {
        $middleware  = new TrimStrings();
        $destination = function ($request) { return Responder::toResponse('OK'); };

        $request  = $this->createRequestMock('/');
        $request->query->add([
            'q_null'            => null,
            'q_empty_string'    => '',
            'q_trim_string'     => ' a  ',
            'q_mb_trim_string'  => '　b　　',
            'q_mix_trim_string' => ' 　 b c　 　',
            'q_empty_array'     => [],
            'q_number'          => 1,
            'q_array'           => [1, ' a ', 3, '　b　', 5],
            'q_map'             => [
                'q_null'            => null,
                'q_empty_string'    => '',
                'q_trim_string'     => ' a  ',
                'q_mb_trim_string'  => '　b　　',
                'q_mix_trim_string' => ' 　 b c　 　',
                'q_empty_array'     => [],
                'q_number'          => 1,
                'q_array'           => [1, ' a ', 3, '　b　', 5],
            ]
        ]);
        $request->request->add([
            'r_null'            => null,
            'r_empty_string'    => '',
            'r_trim_string'     => ' a  ',
            'r_mb_trim_string'  => '　b　　',
            'r_mix_trim_string' => ' 　 b c　 　',
            'r_empty_array'     => [],
            'r_number'          => 1,
            'r_array'           => [1, ' a ', 3, '　b　', 5],
            'r_map'             => [
                'r_null'            => null,
                'r_empty_string'    => '',
                'r_trim_string'     => ' a  ',
                'r_mb_trim_string'  => '　b　　',
                'r_mix_trim_string' => ' 　 b c　 　',
                'r_empty_array'     => [],
                'r_number'          => 1,
                'r_array'           => [1, ' a ', 3, '　b　', 5],
            ]
        ]);

        $response = $middleware->handle($request, $destination);
        $this->assertInstanceOf(BasicResponse::class, $response);
        $this->assertSame('OK', $response->getContent());
        $this->assertSame([
            'r_null'            => null,
            'r_empty_string'    => '',
            'r_trim_string'     => 'a',
            'r_mb_trim_string'  => 'b',
            'r_mix_trim_string' => 'b c',
            'r_empty_array'     => [],
            'r_number'          => 1,
            'r_array'           => [1, 'a', 3, 'b', 5],
            'r_map'             => [
                'r_null'            => null,
                'r_empty_string'    => '',
                'r_trim_string'     => 'a',
                'r_mb_trim_string'  => 'b',
                'r_mix_trim_string' => 'b c',
                'r_empty_array'     => [],
                'r_number'          => 1,
                'r_array'           => [1, 'a', 3, 'b', 5],
            ],
            'q_null'            => null,
            'q_empty_string'    => '',
            'q_trim_string'     => 'a',
            'q_mb_trim_string'  => 'b',
            'q_mix_trim_string' => 'b c',
            'q_empty_array'     => [],
            'q_number'          => 1,
            'q_array'           => [1, 'a', 3, 'b', 5],
            'q_map'             => [
                'q_null'            => null,
                'q_empty_string'    => '',
                'q_trim_string'     => 'a',
                'q_mb_trim_string'  => 'b',
                'q_mix_trim_string' => 'b c',
                'q_empty_array'     => [],
                'q_number'          => 1,
                'q_array'           => [1, 'a', 3, 'b', 5],
            ],
        ], $request->input());
    }
}
