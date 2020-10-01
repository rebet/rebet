<?php
namespace Rebet\Tests\Middleware\Routing;

use PHPUnit\Framework\AssertionFailedError;
use Rebet\Tools\Utility\Nets;
use Rebet\Tools\Utility\Securities;
use Rebet\Tools\Utility\Strings;
use Rebet\Http\Exception\TokenMismatchException;
use Rebet\Http\Responder;
use Rebet\Http\Response\BasicResponse;
use Rebet\Http\Session\Session;
use Rebet\Middleware\Routing\VerifyCsrfToken;
use Rebet\Tests\RebetTestCase;

class VerifyCsrfTokenTest extends RebetTestCase
{
    public function test___construct()
    {
        $this->assertInstanceOf(VerifyCsrfToken::class, new VerifyCsrfToken());
        $this->assertInstanceOf(VerifyCsrfToken::class, new VerifyCsrfToken(['/exclude/path/*']));
        $this->assertInstanceOf(VerifyCsrfToken::class, new VerifyCsrfToken(['/exclude/path/*'], false));
        $this->assertInstanceOf(VerifyCsrfToken::class, new VerifyCsrfToken(['/exclude/path/*'], true, '2 hour'));
    }

    public function dataHandles() : array
    {
        return [
            [true , '/article/edit/1', 'GET'    , [], true , [], true ],
            [true , '/article/edit/1', 'HEAD'   , [], true , [], true ],
            [true , '/article/edit/1', 'OPTIONS', [], true , [], true ],
            [true , '/article/edit/1', 'POST'   , [], true , [], true ],
            [true , '/article/edit/1', 'PUT'    , [], true , [], true ],
            [true , '/article/edit/1', 'DELETE' , [], true , [], true ],

            [true , '/article/edit/1', 'GET'    , [], false, [], true ],
            [true , '/article/edit/1', 'HEAD'   , [], false, [], true ],
            [true , '/article/edit/1', 'OPTIONS', [], false, [], true ],
            [false, '/article/edit/1', 'POST'   , [], false, [], true ],
            [false, '/article/edit/1', 'PUT'    , [], false, [], true ],
            [false, '/article/edit/1', 'DELETE' , [], false, [], true ],

            [true , '/article/edit/1'      , 'POST', ['/article/edit*'       ], false, [], true ],
            [true , '/article/edit/2'      , 'POST', ['/article/edit*'       ], false, [], true ],
            [true , '/article/edit-confirm', 'POST', ['/article/edit*'       ], false, [], true ],
            [false, '/'                    , 'POST', ['/article/edit*'       ], false, [], true ],
            [false, '/article/register'    , 'POST', ['/article/edit*'       ], false, [], true ],
            [false, '/article/edit-confirm', 'POST', ['/article/edit'        ], false, [], true ],
            [true , '/article/edit-confirm', 'POST', ['/article/edit-confirm'], false, [], true ],
            [true , '/foo'                 , 'POST', ['/foo', '/bar'         ], false, [], true ],
            [true , '/bar'                 , 'POST', ['/foo', '/bar'         ], false, [], true ],
            [false, '/baz'                 , 'POST', ['/foo', '/bar'         ], false, [], true ],

            [true , '/article/edit/1', 'POST', [            ], true, ['article', 'edit', 1], true ],
            [false, '/article/edit/1', 'POST', [            ], true, ['article', 'edit', 1], false],
            [true , '/article/edit/1', 'POST', ['/article/*'], true, ['article', 'edit', 1], false],
            [true , '/article/edit/1', 'GET' , [            ], true, ['article', 'edit', 1], false],
        ];
    }

    /**
     * @dataProvider dataHandles
     */
    public function test_handle(bool $expect, string $path, string $method, array $excludes = [], bool $token_match = true, array $scope = [], bool $scope_match = true)
    {
        $middleware  = new VerifyCsrfToken($excludes);
        $destination = function ($request) { return Responder::toResponse('OK'); };
        $request     = $this->createRequestMock($path, null, 'web', $method);
        $token       = $request->session()->generateToken(...$scope);
        $token       = $token_match ? $token : "X-{$token}" ;
        $key         = $scope_match ? Session::createTokenKey(...$scope) : Session::createTokenKey(...array_merge($scope, ['mismatch'])) ;
        $request->request->set($key, $token);

        try {
            $response = $middleware->handle($request, $destination);
            $this->assertInstanceOf(BasicResponse::class, $response);
            $this->assertSame(200, $response->getStatusCode());
            if (!$expect) {
                $this->fail('Expect result is csrf verification failed. but the verification is passed.');
            } else {
                try {
                    $response = $middleware->handle($request, $destination);
                    $this->assertInstanceOf(BasicResponse::class, $response);
                    $this->assertSame(200, $response->getStatusCode());
                    if (!empty($scope) && !in_array($method, ['GET', 'HEAD', 'OPTIONS']) && !Strings::wildmatch($path, $excludes)) {
                        $this->fail('2nd time access expects csrf verification failed when one time token. but the verification is passed.');
                    }
                } catch (AssertionFailedError $e) {
                    throw $e;
                } catch (\Throwable $e) {
                    $this->assertInstanceOf(TokenMismatchException::class, $e);
                    if (empty($scope)) {
                        $this->fail('2nd time access expects csrf verification passed when reusable token. but the verification is failed.');
                    }
                }
            }
        } catch (AssertionFailedError $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->assertInstanceOf(TokenMismatchException::class, $e);
            if ($expect) {
                $this->fail('Expect result is csrf verification passed. but the verification is failed.');
            }
        }
    }

    public function test_handle_multiOnetimeTocken()
    {
        $middleware  = new VerifyCsrfToken();
        $destination = function ($request) { return Responder::toResponse('OK'); };
        $request     = $this->createRequestMock('/', null, 'web', 'POST');
        $token       = $request->session()->generateToken();
        $token_1     = $request->session()->generateToken('article', 'edit', 1);
        $token_2     = $request->session()->generateToken('article', 'edit', 2);
        $key_1       = Session::createTokenKey('article', 'edit', 1);
        $key_2       = Session::createTokenKey('article', 'edit', 2);

        $request->request->set($key_1, $token_1);
        $response = $middleware->handle($request, $destination);
        $this->assertInstanceOf(BasicResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $request->request->remove($key_1);

        $request->request->set($key_2, $token_2);
        $response = $middleware->handle($request, $destination);
        $this->assertInstanceOf(BasicResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $request->request->remove($key_2);

        $request->request->set('_token', $token);
        $response = $middleware->handle($request, $destination);
        $this->assertInstanceOf(BasicResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $request->request->remove('_token');

        try {
            $request->request->set($key_2, $token_2);
            $response = $middleware->handle($request, $destination);
            $this->fail('2nd time access expects csrf verification failed when one time token. but the verification is passed.');
        } catch (\Throwable $e) {
            $this->assertInstanceOf(TokenMismatchException::class, $e);
        }
    }

    public function test_handle_xcsrf()
    {
        $middleware  = new VerifyCsrfToken();
        $destination = function ($request) { return Responder::toResponse('OK'); };
        $request     = $this->createRequestMock('/', null, 'web', 'POST');
        $token       = $request->session()->generateToken();

        $request->headers->set('X-CSRF-TOKEN', $token);
        $response = $middleware->handle($request, $destination);
        $this->assertInstanceOf(BasicResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_handle_xxsrf()
    {
        $middleware  = new VerifyCsrfToken([], true);
        $destination = function ($request) { return Responder::toResponse('OK'); };
        $request     = $this->createRequestMock('/', null, 'web', 'POST');
        $token       = $request->session()->generateToken();

        $request->headers->set('X-XSRF-TOKEN', Nets::encodeBase64Url(Securities::encrypt($token)));
        $response = $middleware->handle($request, $destination);
        $this->assertInstanceOf(BasicResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($token, Securities::decrypt(Nets::decodeBase64Url($response->getCookie('XSRF-TOKEN')->getValue())));
    }

    public function test_handle_xxsrf_unsupport()
    {
        $this->expectException(TokenMismatchException::class);

        $middleware  = new VerifyCsrfToken([], false);
        $destination = function ($request) { return Responder::toResponse('OK'); };
        $request     = $this->createRequestMock('/', null, 'web', 'POST');
        $token       = $request->session()->generateToken();

        $request->headers->set('X-XSRF-TOKEN', Nets::encodeBase64Url(Securities::encrypt($token)));
        $response = $middleware->handle($request, $destination);
    }
}
