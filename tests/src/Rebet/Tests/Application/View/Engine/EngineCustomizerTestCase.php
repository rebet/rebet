<?php
namespace Rebet\Tests\Application\View\Engine;

use Rebet\Database\Pagination\Paginator;
use Rebet\Http\Session\Session;
use Rebet\Tests\Mock\Entity\User;
use Rebet\Tests\RebetTestCase;
use Rebet\Validation\BuiltinValidations;
use Rebet\View\Engine\Engine;
use Rebet\View\EofLineFeed;

abstract class EngineCustomizerTestCase extends RebetTestCase
{
    protected $engine;

    abstract protected function createEngine() : Engine;

    public function setUp()
    {
        parent::setUp();
        $this->signout();
        $this->vfs([
            'cache' => [],
        ]);

        $this->engine = $this->createEngine();
    }

    protected function render(string $name, array $args = []) : ?string
    {
        return EofLineFeed::TRIM()->process($this->engine->render($name, $args));
    }

    public function test_tag_env()
    {
        \putenv("APP_ENV=unittest");
        $this->assertSame(
            <<<EOS
unittest
unittest or local
Not production.
EOS
            ,
            $this->render('custom/env')
        );

        \putenv("APP_ENV=local");
        $this->assertSame(
            <<<EOS
unittest or local
Not production.
EOS
            ,
            $this->render('custom/env')
        );
    }

    public function test_tag_prefix()
    {
        $this->assertSame('/controller/action/arg1', $this->render('custom/prefix'));
        $this->assertSame('/controller/action/arg1', $this->render('custom/prefix', ['prefix' => null]));
        $this->assertSame('/rebet/controller/action/arg1', $this->render('custom/prefix', ['prefix' => '/rebet']));
    }

    public function test_tag_role()
    {
        $this->assertSame(
            <<<EOS
Guest
EOS
            ,
            $this->render('custom/role')
        );

        $request = $this->createRequestMock('/');
        $this->signin($request);

        $this->assertSame(
            <<<EOS
user
admin or user
Not Guest.
EOS
            ,
            $this->render('custom/role')
        );
        $this->signout();

        $this->signin($request, 'user.editable@rebet.local', 'user.editable');
        $this->assertSame(
            <<<EOS
user
user and editable
admin or user
Not Guest.
EOS
            ,
            $this->render('custom/role')
        );
        $this->signout();

        $this->signin($request, 'admin@rebet.local', 'admin');
        $this->assertSame(
            <<<EOS
admin
admin or user
Not Guest.
EOS
            ,
            $this->render('custom/role')
        );
    }

    public function test_tag_can()
    {
        $user          = new User();
        $user->user_id = 2;

        $this->assertSame(
            <<<EOS
can not update user
Can not create an address when the user is guest or the addresses count greater equal 5.
EOS
            ,
            $this->render('custom/can', ['user' => $user, 'addresses' => []])
        );

        $request = $this->createRequestMock('/');
        $this->signin($request);

        $this->assertSame(
            <<<EOS
can update user
Can create an address when the addresses count less than 5.
EOS
            ,
            $this->render('custom/can', ['user' => $user, 'addresses' => []])
        );
        $this->signout();

        $this->signin($request, 'user.editable@rebet.local', 'user.editable');
        $this->assertSame(
            <<<EOS
can not update user
can create user(absolute class name 1)
can create user(absolute class name 2)
can create user(relative class name)
Can create an address when the addresses count less than 5.
EOS
            ,
            $this->render('custom/can', ['user' => $user, 'addresses' => []])
        );
        $this->signout();

        $this->signin($request, 'admin@rebet.local', 'admin');
        $this->assertSame(
            <<<EOS
can update user
can create user(absolute class name 1)
can create user(absolute class name 2)
can create user(relative class name)
Can create an address when the addresses count less than 5.
EOS
            ,
            $this->render('custom/can', ['user' => $user, 'addresses' => [1, 2, 3, 4]])
        );

        $this->assertSame(
            <<<EOS
can update user
can create user(absolute class name 1)
can create user(absolute class name 2)
can create user(relative class name)
Can not create an address when the user is guest or the addresses count greater equal 5.
EOS
            ,
            $this->render('custom/can', ['user' => $user, 'addresses' => [1, 2, 3, 4, 5]])
        );
    }

    public function test_tag_field()
    {
        $this->assertSame(
            <<<EOS
[1] 
[2] name
[3] 
[4]  ;
[5] ---===
EOS
            ,
            $this->render('custom/field')
        );
    }

    public function test_tag_errors()
    {
        $errors = [];

        $this->assertSame(
            <<<EOS
Has not any error.
EOS
            ,
            $this->render('custom/errors', ['errors' => $errors])
        );

        $errors = [
            'name' => [
                'The name field is required.'
            ]
        ];

        $this->assertSame(
            <<<EOS
Has some error.
Has some error about 'name'.
Has some error about 'name' (Under field of 'email').
EOS
            ,
            $this->render('custom/errors', ['errors' => $errors])
        );

        $errors = [
            'email' => [
                'The email field is required.'
            ]
        ];

        $this->assertSame(
            <<<EOS
Has some error.
Has some error about 'email'.
Has some error about 'email' (Under field of 'email').
EOS
            ,
            $this->render('custom/errors', ['errors' => $errors])
        );

        $errors = [
            'name' => [
                'The name field is required.'
            ],
            'email' => [
                'The email field is required.'
            ]
        ];

        $this->assertSame(
            <<<EOS
Has some error.
Has some error about 'name'.
Has some error about 'email'.
Has some error about 'email' (Under field of 'email').
Has some error about 'name' (Under field of 'email').
EOS
            ,
            $this->render('custom/errors', ['errors' => $errors])
        );
    }

    public function test_tag_error()
    {
        $errors = [];

        $this->assertSame(
            <<<EOS
[1] 
[2] 
[3] 
[4] 
[5] 
[6] 
[7] 
[8] 
EOS
            ,
            $this->render('custom/error', ['errors' => $errors])
        );

        $errors = [
            'name' => [
                'The name field is required.',
                'The name may not be greater than 20 characters.'
            ]
        ];
        $this->assertSame(
            <<<EOS
[1] <ul class="error"><li>The name field is required.</li><li>The name may not be greater than 20 characters.</li></ul>
[2] <ul class="error"><li>The name field is required.</li><li>The name may not be greater than 20 characters.</li></ul>
[3] =====
 * The name field is required.
 * The name may not be greater than 20 characters.
=====
[4] <ul class="error"> * The name field is required.
 * The name may not be greater than 20 characters.
</ul>
[5] 
[6] 
[7] 
[8] 
EOS
            ,
            $this->render('custom/error', ['errors' => $errors])
        );

        $errors = [
            'email' => [
                'The email field is required.',
                'The email may not be greater than 255 characters.'
            ]
        ];
        $this->assertSame(
            <<<EOS
[1] <ul class="error"><li>The email field is required.</li><li>The email may not be greater than 255 characters.</li></ul>
[2] 
[3] 
[4] 
[5] <ul class="error"><li>The email field is required.</li><li>The email may not be greater than 255 characters.</li></ul>
[6] <ul class="error"><li>The email field is required.</li><li>The email may not be greater than 255 characters.</li></ul>
[7] =====
 * The email field is required.
 * The email may not be greater than 255 characters.
=====
[8] <ul class="error"> * The email field is required.
 * The email may not be greater than 255 characters.
</ul>
EOS
            ,
            $this->render('custom/error', ['errors' => $errors])
        );

        $errors = [
            'name' => [
                'The name field is required.',
                'The name may not be greater than 20 characters.'
            ],
            'email' => [
                'The email field is required.',
            ]
        ];
        $this->assertSame(
            <<<EOS
[1] <ul class="error"><li>The name field is required.</li><li>The name may not be greater than 20 characters.</li><li>The email field is required.</li></ul>
[2] <ul class="error"><li>The name field is required.</li><li>The name may not be greater than 20 characters.</li></ul>
[3] =====
 * The name field is required.
 * The name may not be greater than 20 characters.
=====
[4] <ul class="error"> * The name field is required.
 * The name may not be greater than 20 characters.
</ul>
[5] <ul class="error"><li>The email field is required.</li></ul>
[6] <ul class="error"><li>The email field is required.</li></ul>
[7] =====
 * The email field is required.
=====
[8] <ul class="error"> * The email field is required.
</ul>
EOS
            ,
            $this->render('custom/error', ['errors' => $errors])
        );
    }

    public function test_tag_iferror()
    {
        $errors = [];

        $this->assertSame(
            <<<EOS
[1] 
[2] name has not error
[3] email has not error
[4] 
[5] email has not error in field
EOS
            ,
            $this->render('custom/iferror', ['errors' => $errors])
        );

        $errors = [
            'name' => [
                'The name field is required.',
                'The name may not be greater than 20 characters.'
            ]
        ];
        $this->assertSame(
            <<<EOS
[1] name has error
[2] name has error
[3] email has not error
[4] 
[5] email has not error in field
EOS
            ,
            $this->render('custom/iferror', ['errors' => $errors])
        );

        $errors = [
            'email' => [
                'The email field is required.',
                'The email may not be greater than 255 characters.'
            ]
        ];
        $this->assertSame(
            <<<EOS
[1] 
[2] name has not error
[3] email has error
[4] email has error in field
[5] email has error in field
EOS
            ,
            $this->render('custom/iferror', ['errors' => $errors])
        );

        $errors = [
            'name' => [
                'The name field is required.',
                'The name may not be greater than 20 characters.'
            ],
            'email' => [
                'The email field is required.',
            ]
        ];
        $this->assertSame(
            <<<EOS
[1] name has error
[2] name has error
[3] email has error
[4] email has error in field
[5] email has error in field
EOS
            ,
            $this->render('custom/iferror', ['errors' => $errors])
        );
    }

    public function test_tag_e()
    {
        $errors = [];

        $this->assertSame(
            <<<EOS
[1] 
[2] #333
[3] 
[4] 
EOS
            ,
            $this->render('custom/e', ['errors' => $errors])
        );

        $errors = [
            'name' => [
                'The name field is required.',
                'The name may not be greater than 20 characters.'
            ]
        ];
        $this->assertSame(
            <<<EOS
[1] is-danger
[2] red
[3] 
[4] 
EOS
            ,
            $this->render('custom/e', ['errors' => $errors])
        );

        $errors = [
            'email' => [
                'The email field is required.',
                'The email may not be greater than 255 characters.'
            ]
        ];
        $this->assertSame(
            <<<EOS
[1] 
[2] #333
[3] is-danger
[4] is-danger
EOS
            ,
            $this->render('custom/e', ['errors' => $errors])
        );

        $errors = [
            'name' => [
                'The name field is required.',
                'The name may not be greater than 20 characters.'
            ],
            'email' => [
                'The email field is required.',
            ]
        ];
        $this->assertSame(
            <<<EOS
[1] is-danger
[2] red
[3] is-danger
[4] is-danger
EOS
            ,
            $this->render('custom/e', ['errors' => $errors])
        );
    }

    public function test_tag_input()
    {
        $input = [];

        $this->assertSame(
            <<<EOS
[1] 
[2] default
[3] default
[4] 
[5] default
EOS
            ,
            $this->render('custom/input', ['input' => $input])
        );

        $input = [
            'name' => 'Name'
        ];
        $this->assertSame(
            <<<EOS
[1] Name
[2] Name
[3] default
[4] 
[5] default
EOS
            ,
            $this->render('custom/input', ['input' => $input])
        );

        $input = [
            'email' => 'test@rebet.local'
        ];
        $this->assertSame(
            <<<EOS
[1] 
[2] default
[3] test@rebet.local
[4] test@rebet.local
[5] test@rebet.local
EOS
            ,
            $this->render('custom/input', ['input' => $input])
        );

        $input = [
            'name'  => 'Name',
            'email' => 'test@rebet.local'
        ];
        $this->assertSame(
            <<<EOS
[1] Name
[2] Name
[3] test@rebet.local
[4] test@rebet.local
[5] test@rebet.local
EOS
            ,
            $this->render('custom/input', ['input' => $input])
        );
    }

    public function test_tag_csrf_token()
    {
        $session = new Session();
        $session->start();

        $reusable_token       = $session->generateToken();
        $user_edit_token      = $session->generateToken('user', 'edit');
        $article_edit_1_token = $session->generateToken('article', 'edit', 1);
        $article_edit_2_token = $session->generateToken('article', 'edit', 2);

        $actual         = $this->render('custom/csrf_token', ['article_id' => 1]);
        $direct_1_token = $session->token('direct', 1);
        $this->assertSame(
            <<<EOS
[1] {$reusable_token}
[2] {$user_edit_token}
[3] {$article_edit_1_token}
[4] {$direct_1_token}
EOS
            ,
            $actual
        );

        $actual         = $this->render('custom/csrf_token', ['article_id' => 2]);
        $direct_2_token = $session->token('direct', 2);
        $this->assertSame(
            <<<EOS
[1] {$reusable_token}
[2] {$user_edit_token}
[3] {$article_edit_2_token}
[4] {$direct_2_token}
EOS
            ,
            $actual
        );
    }

    public function test_tag_csrf()
    {
        $session = new Session();
        $session->start();

        $reusable_token           = $session->generateToken();
        $reusable_token_key       = Session::createTokenKey();
        $user_edit_token          = $session->generateToken('user', 'edit');
        $user_edit_token_key      = Session::createTokenKey('user', 'edit');
        $article_edit_1_token     = $session->generateToken('article', 'edit', 1);
        $article_edit_1_token_key = Session::createTokenKey('article', 'edit', 1);
        $article_edit_2_token     = $session->generateToken('article', 'edit', 2);
        $article_edit_2_token_key = Session::createTokenKey('article', 'edit', 2);

        $actual             = $this->render('custom/csrf', ['article_id' => 1]);
        $direct_1_token     = $session->token('direct', 1);
        $direct_1_token_key = Session::createTokenKey('direct', 1);
        $this->assertSame(
            <<<EOS
[1] <input type="hidden" name="{$reusable_token_key}" value="{$reusable_token}" />
[2] <input type="hidden" name="{$user_edit_token_key}" value="{$user_edit_token}" />
[3] <input type="hidden" name="{$article_edit_1_token_key}" value="{$article_edit_1_token}" />
[4] <input type="hidden" name="{$direct_1_token_key}" value="{$direct_1_token}" />
EOS
            ,
            $actual
        );

        $actual             = $this->render('custom/csrf', ['article_id' => 2]);
        $direct_2_token     = $session->token('direct', 2);
        $direct_2_token_key = Session::createTokenKey('direct', 2);
        $this->assertSame(
            <<<EOS
[1] <input type="hidden" name="{$reusable_token_key}" value="{$reusable_token}" />
[2] <input type="hidden" name="{$user_edit_token_key}" value="{$user_edit_token}" />
[3] <input type="hidden" name="{$article_edit_2_token_key}" value="{$article_edit_2_token}" />
[4] <input type="hidden" name="{$direct_2_token_key}" value="{$direct_2_token}" />
EOS
            ,
            $actual
        );
    }

    public function test_tag_lang()
    {
        $validator = new BuiltinValidations(); // load validation translate file
        $this->assertSame(
            <<<EOS
[1] ようこそ、Jhon様
[2] タグは1個以下で選択して下さい。
[3] タグは3個以下で選択して下さい。
[4] The Tag may not have more than 1 item.
[5] The Tag may not have more than 3 items.
EOS
            ,
            $this->render('custom/lang')
        );
    }

    public function dataPaginates() : array
    {
        return [
            [
                [
                    '/前へ/',
                    '/次へ/',
                    '/\/users\/search/',
                    '/gender=1/',
                    '/status=2/',
                ],
                [
                    '/該当件数/',
                    '/最初へ/',
                    '/最後へ/',
                ],
                '/users/search?gender=1&status=2',
                [
                    'template' => 'paginate@bootstrap-4',
                ],
            ],
        ];
    }

    /**
     * @dataProvider dataPaginates
     */
    public function test_tag_paginate(array $expect, array $not_expect, string $action, array $options, int $each_side = 3, int $page_size = 3, ?int $page = 1, ?int $total = null, ?int $next_page_count = 4)
    {
        $this->assertTrue(true);

        // @todo
        $request    = $this->createRequestMock($action);
        $pagination = $this->render('custom/paginate', ['users' => new Paginator([], $each_side, $page_size, $page, $total, $next_page_count), 'options' => $options]);
        $this->assertRegExpString($expect, $pagination);
        $this->assertNotRegExpString($not_expect, $pagination);
    }
}
