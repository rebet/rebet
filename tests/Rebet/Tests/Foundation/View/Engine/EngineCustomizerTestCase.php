<?php
namespace Rebet\Tests\Foundation\View\Engine;

use Rebet\Foundation\App;
use Rebet\Tests\Common\Mock\User;
use Rebet\Tests\RebetTestCase;
use Rebet\View\Engine\Engine;

abstract class EngineCustomizerTestCase extends RebetTestCase
{
    private $engine;

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

    public function test_render_env()
    {
        App::setEnv('unittest');
        $this->assertSame(
            <<<EOS
unittest
unittest or local
Not production.

EOS
            ,
            $this->engine->render('custom/env')
        );

        App::setEnv('local');
        $this->assertSame(
            <<<EOS
unittest or local
Not production.

EOS
            ,
            $this->engine->render('custom/env')
        );
    }

    public function test_render_prefix()
    {
        $this->assertSame('/controller/action/arg1', $this->engine->render('custom/prefix'));
        $this->assertSame('/controller/action/arg1', $this->engine->render('custom/prefix', ['prefix' => null]));
        $this->assertSame('/rebet/controller/action/arg1', $this->engine->render('custom/prefix', ['prefix' => '/rebet']));
    }

    public function test_render_role()
    {
        $this->assertSame(
            <<<EOS
Guest

EOS
            ,
            $this->engine->render('custom/role')
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
            $this->engine->render('custom/role')
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
            $this->engine->render('custom/role')
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
            $this->engine->render('custom/role')
        );
    }

    public function test_render_can()
    {
        $user          = new User();
        $user->user_id = 2;

        $this->assertSame(
            <<<EOS
can not update user
Can not create an address when the user is guest or the addresses count greater equal 5.

EOS
            ,
            $this->engine->render('custom/can', ['user' => $user, 'addresses' => []])
        );

        $request = $this->createRequestMock('/');
        $this->signin($request);

        $this->assertSame(
            <<<EOS
can update user
Can create an address when the addresses count less than 5.

EOS
            ,
            $this->engine->render('custom/can', ['user' => $user, 'addresses' => []])
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
            $this->engine->render('custom/can', ['user' => $user, 'addresses' => []])
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
            $this->engine->render('custom/can', ['user' => $user, 'addresses' => [1, 2, 3, 4]])
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
            $this->engine->render('custom/can', ['user' => $user, 'addresses' => [1, 2, 3, 4, 5]])
        );
    }

    public function test_render_field()
    {
        $this->assertSame(
            <<<EOS
[1] ;
[2] name;
[3] ;

EOS
            ,
            $this->engine->render('custom/field')
        );
    }

    public function test_render_errors()
    {
        $errors = [];

        $this->assertSame(
            <<<EOS
Has not any error.

EOS
            ,
            $this->engine->render('custom/errors', ['errors' => $errors])
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
            $this->engine->render('custom/errors', ['errors' => $errors])
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
            $this->engine->render('custom/errors', ['errors' => $errors])
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
            $this->engine->render('custom/errors', ['errors' => $errors])
        );
    }

    public function test_render_error()
    {
        $errors = [];

        $this->assertSame(
            <<<EOS
---
---
---
---
---

EOS
            ,
            $this->engine->render('custom/error', ['errors' => $errors])
        );

        $errors = [
            'name' => [
                'The name field is required.',
                'The name may not be greater than 20 characters.'
            ]
        ];
        $this->assertSame(
            <<<EOS
<ul class="error"><li>The name field is required.</li><li>The name may not be greater than 20 characters.</li></ul>
---
<ul class="error"><li>The name field is required.</li><li>The name may not be greater than 20 characters.</li></ul>
---
=====
 * The name field is required.
 * The name may not be greater than 20 characters.
=====
---
---
---

EOS
            ,
            $this->engine->render('custom/error', ['errors' => $errors])
        );

        $errors = [
            'email' => [
                'The email field is required.',
                'The email may not be greater than 255 characters.'
            ]
        ];
        $this->assertSame(
            <<<EOS
<ul class="error"><li>The email field is required.</li><li>The email may not be greater than 255 characters.</li></ul>
---
---
---
<ul class="error"><li>The email field is required.</li><li>The email may not be greater than 255 characters.</li></ul>
---
<ul class="error"><li>The email field is required.</li><li>The email may not be greater than 255 characters.</li></ul>
---
=====
 * The email field is required.
 * The email may not be greater than 255 characters.
=====

EOS
            ,
            $this->engine->render('custom/error', ['errors' => $errors])
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
<ul class="error"><li>The name field is required.</li><li>The name may not be greater than 20 characters.</li><li>The email field is required.</li></ul>
---
<ul class="error"><li>The name field is required.</li><li>The name may not be greater than 20 characters.</li></ul>
---
=====
 * The name field is required.
 * The name may not be greater than 20 characters.
=====
---
<ul class="error"><li>The email field is required.</li></ul>
---
<ul class="error"><li>The email field is required.</li></ul>
---
=====
 * The email field is required.
=====

EOS
            ,
            $this->engine->render('custom/error', ['errors' => $errors])
        );
    }

    public function test_render_iferror()
    {
        $errors = [];

        $this->assertSame(
            <<<EOS
---
name has not error---
email has not error---
---
email has not error in field
EOS
            ,
            $this->engine->render('custom/iferror', ['errors' => $errors])
        );

        $errors = [
            'name' => [
                'The name field is required.',
                'The name may not be greater than 20 characters.'
            ]
        ];
        $this->assertSame(
            <<<EOS
name has error---
name has error---
email has not error---
---
email has not error in field
EOS
            ,
            $this->engine->render('custom/iferror', ['errors' => $errors])
        );

        $errors = [
            'email' => [
                'The email field is required.',
                'The email may not be greater than 255 characters.'
            ]
        ];
        $this->assertSame(
            <<<EOS
---
name has not error---
email has error---
email has error in field---
email has error in field
EOS
            ,
            $this->engine->render('custom/iferror', ['errors' => $errors])
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
name has error---
name has error---
email has error---
email has error in field---
email has error in field
EOS
            ,
            $this->engine->render('custom/iferror', ['errors' => $errors])
        );
    }

    public function test_render_e()
    {
        $errors = [];

        $this->assertSame(
            <<<EOS
---
#333---
---

EOS
            ,
            $this->engine->render('custom/e', ['errors' => $errors])
        );

        $errors = [
            'name' => [
                'The name field is required.',
                'The name may not be greater than 20 characters.'
            ]
        ];
        $this->assertSame(
            <<<EOS
is-danger---
red---
---

EOS
            ,
            $this->engine->render('custom/e', ['errors' => $errors])
        );

        $errors = [
            'email' => [
                'The email field is required.',
                'The email may not be greater than 255 characters.'
            ]
        ];
        $this->assertSame(
            <<<EOS
---
#333---
is-danger---
is-danger
EOS
            ,
            $this->engine->render('custom/e', ['errors' => $errors])
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
is-danger---
red---
is-danger---
is-danger
EOS
            ,
            $this->engine->render('custom/e', ['errors' => $errors])
        );
    }

    public function test_render_input()
    {
        $input = [];

        $this->assertSame(
            <<<EOS
---
default---
default---
---
default
EOS
            ,
            $this->engine->render('custom/input', ['input' => $input])
        );

        $input = [
            'name' => 'Name'
        ];
        $this->assertSame(
            <<<EOS
Name---
Name---
default---
---
default
EOS
            ,
            $this->engine->render('custom/input', ['input' => $input])
        );

        $input = [
            'email' => 'test@rebet.local'
        ];
        $this->assertSame(
            <<<EOS
---
default---
test@rebet.local---
test@rebet.local---
test@rebet.local
EOS
            ,
            $this->engine->render('custom/input', ['input' => $input])
        );

        $input = [
            'name'  => 'Name',
            'email' => 'test@rebet.local'
        ];
        $this->assertSame(
            <<<EOS
Name---
Name---
test@rebet.local---
test@rebet.local---
test@rebet.local
EOS
            ,
            $this->engine->render('custom/input', ['input' => $input])
        );
    }
}
