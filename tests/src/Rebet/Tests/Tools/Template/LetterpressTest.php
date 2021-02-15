<?php
namespace Rebet\Tests\Tools\Template;

use Rebet\Application\App;
use Rebet\Auth\Auth;
use Rebet\Tests\RebetTestCase;
use Rebet\Tools\Exception\LogicException;
use Rebet\Tools\Math\Decimal;
use Rebet\Tools\Reflection\Reflector;
use Rebet\Tools\Template\Letterpress;
use Rebet\Tools\Utility\Arrays;
use Rebet\Tools\Utility\Strings;

class LetterpressTest extends RebetTestCase
{
    protected function setUp() : void
    {
        parent::setUp();
    }

    public function dataCompiles() : array
    {
        return [
            [
                null,
                []
            ],
            [
                "",
                []
            ],
            [
                "foo",
                [
                    'foo'
                ]
            ],
            [
                "foo {{ \$var }} bar",
                [
                    "foo {{ \$var }} bar"
                ]
            ],
            [
                "foo {#coment-#} bar",
                [
                    "foo bar"
                ]
            ],
            [
                "foo {#coment#} \nbar",
                [
                    "foo  \nbar"
                ]
            ],
            [
                "foo {#-coment#} \nbar",
                [
                    "foo \nbar"
                ]
            ],
            [
                "foo\n {#-coment#} \nbar",
                [
                    "foo\n \nbar"
                ]
            ],
            [
                "foo {#coment-#} \nbar",
                [
                    "foo bar"
                ]
            ],
            [
                "foo {#- coment -#} \nbar",
                [
                    "foobar"
                ]
            ],
            [
                "foo {#- coment -#} \n bar",
                [
                    "foo bar"
                ]
            ],
            [
                "foo {#- coment -#} \n\n bar",
                [
                    "foo\n bar"
                ]
            ],
            [
                "foo /*{#- coment -#}*/ \n\n bar",
                [
                    "foo  \n\n bar"
                ]
            ],
            [
                "foo /*{#-- coment --#}*/ \n\n bar",
                [
                    "foo\n bar"
                ]
            ],
            [
                "foo {#-- coment --#} \n\n bar",
                [
                    "foo\n bar"
                ]
            ],
            [
                "foo {# coment #} bar {# coment\n #} baz",
                [
                    "foo  bar  baz"
                ]
            ],
            [
                "foo {#- coment #} bar {# coment\n -#} baz",
                [
                    "foo bar baz"
                ]
            ],
            [
                "{% if \$user->isAdmin() %} foo {% endif %}",
                [
                    '',
                    ['tag' => "if", 'code' => "\$user->isAdmin()", 'nodes' => [
                        " foo "
                    ]],
                ]
            ],
            [
                "foo {% if \$user->isAdmin() %} bar {% endif %} baz",
                [
                    "foo ",
                    ['tag' => "if", 'code' => "\$user->isAdmin()", 'nodes' => [
                        " bar "
                    ]],
                    " baz",
                ]
            ],
            [
                "foo {%- if \$user->isAdmin() -%} bar {%- endif -%} baz",
                [
                    "foo",
                    ['tag' => "if", 'code' => "\$user->isAdmin()", 'nodes' => [
                        "bar"
                    ]],
                    "baz",
                ]
            ],
            [
                "foo /*{%- if true -%}*/ bar /*{%- endif -%}*/ baz",
                [
                    "foo ",
                    ['tag' => "if", 'code' => "true", 'nodes' => [
                        " bar "
                    ]],
                    " baz",
                ]
            ],
            [
                "foo /*{%- if true --%}*/ bar /*{%-- endif -%}*/ baz",
                [
                    "foo ",
                    ['tag' => "if", 'code' => "true", 'nodes' => [
                        "bar"
                    ]],
                    " baz",
                ]
            ],
            [
                "foo\n   //{%-- if true --%}\nbar\n   /*{%-- endif --%}*/\nbaz",
                [
                    "foo\n",
                    ['tag' => "if", 'code' => "true", 'nodes' => [
                        "bar\n"
                    ]],
                    "baz",
                ]
            ],
            [
                "foo {% if \$user->isAdmin() %} bar {% else %} baz {% endif %} qux",
                [
                    "foo ",
                    ['tag' => "if", 'code' => "\$user->isAdmin()", 'nodes' => [
                        " bar "
                    ]],
                    ['tag' => "else", 'code' => '', 'nodes' => [
                        " baz "
                    ]],
                    " qux",
                ]
            ],
            [
                "foo {% if \$user->isAdmin() %} bar {% elseif \$user->isMember() %} baz {% else %} qux {% endif %} quxx",
                [
                    "foo ",
                    ['tag' => "if", 'code' => "\$user->isAdmin()", 'nodes' => [
                        " bar "
                    ]],
                    ['tag' => "elseif", 'code' => "\$user->isMember()", 'nodes' => [
                        " baz "
                    ]],
                    ['tag' => "else", 'code' => '', 'nodes' => [
                        " qux "
                    ]],
                    " quxx",
                ]
            ],
            [
                "foo {% if \$user->isAdmin() %} bar {% elseif \$user->isMember() %} baz {% elseif \$user->isGuest() %} qux {% endif %} quxx",
                [
                    "foo ",
                    ['tag' => "if", 'code' => "\$user->isAdmin()", 'nodes' => [
                        " bar "
                    ]],
                    ['tag' => "elseif", 'code' => "\$user->isMember()", 'nodes' => [
                        " baz "
                    ]],
                    ['tag' => "elseif", 'code' => "\$user->isGuest()", 'nodes' => [
                        " qux "
                    ]],
                    " quxx",
                ]
            ],
            [
                "foo {% if true %} bar {% if false %} baz {% endif %} qux {% endif %} quxx",
                [
                    "foo ",
                    ['tag' => "if", 'code' => "true", 'nodes' => [
                        " bar ",
                        ['tag' => "if", 'code' => "false", 'nodes' => [
                            " baz ",
                        ]],
                        " qux ",
                    ]],
                    " quxx",
                ]
            ],
            [
                "a{% if true %}b{% if false %}c{% else %}d{% endif %}e{% else %}f{% endif %}g",
                [
                    "a",
                    ['tag' => "if", 'code' => "true", 'nodes' => [
                        "b",
                        ['tag' => "if", 'code' => "false", 'nodes' => [
                            "c",
                        ]],
                        ['tag' => "else", 'code' => "", 'nodes' => [
                            "d",
                        ]],
                        "e",
                    ]],
                    ['tag' => "else", 'code' => "", 'nodes' => [
                        "f",
                    ]],
                    "g",
                ]
            ],
            [
                'a {% for $list as $key => $value %} {{$key}} = {{$value}} {% endfor %} b',
                [
                    "a ",
                    ['tag' => "for", 'code' => '$list as $key => $value', 'nodes' => [
                        ' {{$key}} = {{$value}} ',
                    ]],
                    " b",
                ]
            ],
            [
                'a {% if true %} b',
                new LogicException('Missing close tag {% endif %}, reached end of template text.'),
            ],
            [
                'a {% endif %} b',
                new LogicException('Missing open tag {% if %} , {% endif %} found.'),
            ],
            [
                'a {% if %} b {% endfor %} c',
                new LogicException('Missing close tag {% endif %}, {% endfor %} found.'),
            ],
            [
                'a {% if %} b {% else %} c {% else %} d {% endif %} e',
                new LogicException('Unsupported (or invalid position) tag {% else %} found.'),
            ],
        ];
    }

    /**
     * @dataProvider dataCompiles
     */
    public function test_compile(?string $template, $expect)
    {
        if ($expect instanceof \Exception) {
            $this->expectException(get_class($expect));
            $this->expectExceptionMessage($expect->getMessage());
        }
        $this->assertSame($expect, $this->inspect(new Letterpress($template), 'syntax'));
    }

    public function dataEvaluates() : array
    {
        return [
            ['$a', [
                1 => [['a' => 1], 1],
                2 => [['a' => 'foo'], 'foo'],
                3 => [['a' => null], null],
            ]],
            ['$a->upper()', [
                1 => [['a' => 1], '1'],
                2 => [['a' => 'foo'], 'FOO'],
            ]],
            ['$a->add($b)', [
                1 => [['a' => 1, 'b' => 2], Decimal::of(3)],
                2 => [['a' => 3, 'b' => 4], Decimal::of(7)],
            ]],
            ['$a->return() + $b->return()', [
                1 => [['a' => 1, 'b' => 2], 3],
                2 => [['a' => 3, 'b' => 4], 7],
            ]],
            ['$a->isInt() ? "int" : "not int"', [
                1 => [['a' => 1], 'int'],
                2 => [['a' => 'foo'], 'not int'],
            ]],
        ];
    }

    /**
     * @dataProvider dataEvaluates
     */
    public function test_evaluate(string $code, array $tests)
    {
        foreach ($tests as $i => [$vars, $expect]) {
            $this->assertEquals($expect, Letterpress::evaluate($code, $vars), ">> {$code} test #{$i}");
        }
    }

    public function test_eval_if()
    {
        $this->expectOutputString('a');
        Letterpress::eval('if($a->isInt()) { echo "a"; } else { echo "b"; }', ['a' => 123]);
    }

    public function test_eval_foreach()
    {
        $this->expectOutputString('1, 2, 3, ');
        Letterpress::eval('foreach($a as $v) { echo $v.", "; }', ['a' => [1, 2, 3]]);
    }

    public function test_eval_foreachCallback()
    {
        $this->expectOutputString(
            <<<EOS
[0] a = [1,2,3]
[0] b = b
[0] i = 0
[0] v = 1
[1] a = [1,2,3]
[1] b = b
[1] i = 1
[1] v = 2
[2] a = [1,2,3]
[2] b = b
[2] i = 2
[2] v = 3

EOS
        );
        Letterpress::eval(
            'foreach($a as $i => $v) { $__callback->invoke($i, compact(array_keys(get_defined_vars()))); }',
            [
                'a'          => [1, 2, 3],
                'b'          => 'b',
                '__callback' => function ($i, $vars) {
                    $vars = Arrays::where($vars, function ($v, $k) { return !Strings::startsWith($k, '__'); });
                    foreach ($vars as $k => $v) {
                        echo "[{$i}] {$k} = {$v}\n";
                    }
                }
            ]
        );
    }

    public function dataExpandVars() : array
    {
        return [
            ['', '', []],
            ['foo', 'foo', []],
            ['a FOO b', 'a {{ $foo }} b', ['foo' => 'FOO']],
            ['aFOO  b', 'a  {{- $foo }}  b', ['foo' => 'FOO']],
            ['a  FOOb', 'a  {{ $foo -}}  b', ['foo' => 'FOO']],
            ['aFOOb', 'a  {{- $foo -}}  b', ['foo' => 'FOO']],
            ['a /*FOO*/ b', 'a /*{{ $foo }}*/ b', ['foo' => 'FOO']],
            ['a FOO b', 'a /*{{- $foo -}}*/ b', ['foo' => 'FOO']],
            ['aFOOb', 'a /*{{-- $foo --}}*/ b', ['foo' => 'FOO']],
            ["a\n/*FOO*/\nb", "a\n/*{{ \$foo }}*/\nb", ['foo' => 'FOO']],
            ["a\nFOO\nb", "a\n/*{{- \$foo -}}*/\nb", ['foo' => 'FOO']],
            ["a\nFOOb", "a\n/*{{-- \$foo --}}*/\nb", ['foo' => 'FOO']],
            ['a  b', 'a {{ $foo }} b', []],
            ['ab', 'a {{- $foo -}} b', []],
            ['a FOO b FOO c', 'a {{ $foo }} b {{ $foo }} c', ['foo' => 'FOO']],
            ['aFOO b FOOc', 'a {{- $foo }} b {{ $foo -}} c', ['foo' => 'FOO']],
            ['a FOO b BAR c', 'a {{ $foo }} b {{ $bar }} c', ['foo' => 'FOO', 'bar' => 'BAR']],
            ['a foo b', 'a {{ $foo->lower() }} b', ['foo' => 'FOO']],
            ['a T b', 'a {{ $foo->equals("FOO") ? "T" : "F" }} b', ['foo' => 'FOO']],
            ['a F b', 'a {{ $foo->equals("BAR") ? "T" : "F" }} b', ['foo' => 'FOO']],
            ['a FOO b', 'a {{ $foo->default("D") }} b', ['foo' => 'FOO']],
            ['a D b', 'a {{ $foo->default("D") }} b', []],
            ['a D b', 'a {{ $foo->default("D") }} b', ['foo' => null]],
            ['a 8,888.89 b', 'a {{ $a->add($b)->numberf(2) }} b', ['a' => 1234.567, 'b' => 7654.321]],
            ['a &amp; b', 'a {{ $a }} b', ['a' => '&']],
            ['a & b', 'a {! $a !} b', ['a' => '&']],
            ['a 1 b', 'a {{ $a }} b', ['a' => 1]],
            ['a 1 b', 'a {! $a !} b', ['a' => 1]],
            ['a /*FOO*/ b', 'a /*{! $foo !}*/ b', ['foo' => 'FOO']],
            ['a FOO b', 'a /*{!- $foo -!}*/ b', ['foo' => 'FOO']],
            ['aFOOb', 'a /*{!-- $foo --!}*/ b', ['foo' => 'FOO']],
            ["a\n/*FOO*/\nb", "a\n/*{! \$foo !}*/\nb", ['foo' => 'FOO']],
            ["a\nFOO\nb", "a\n/*{!- \$foo -!}*/\nb", ['foo' => 'FOO']],
            ["a\nFOOb", "a\n/*{!-- \$foo --!}*/\nb", ['foo' => 'FOO']],
            ['a &amp; b & c', 'a {{ $a }} b {! $a !} c', ['a' => '&']],
            ['a&amp;b&c', 'a {{- $a -}} b {!- $a -!} c', ['a' => '&']],
            [new LogicException("Invalid placeholder format '{{ ... !}' found."), 'a {{ $a !} b', ['a' => 1]],
            [new LogicException("Invalid placeholder format '{! ... }}' found."), 'a {! $a }} b', ['a' => 1]],
        ];
    }

    /**
     * @dataProvider dataExpandVars
     */
    public function test_expandVars($expect, $template, array $vars = [])
    {
        if ($expect instanceof \Exception) {
            $this->expectException(get_class($expect));
            $this->expectExceptionMessage($expect->getMessage());
        }
        $this->assertSame($expect, Letterpress::expandVars($template, $vars));
    }

    public function dataRenders() : array
    {
        return [
            [
                null,
                [],
                ''
            ],
            [
                '',
                [],
                ''
            ],
            [
                'foo',
                [],
                'foo',
            ],
            [
                'foo {{ $var }} bar',
                ['var' => 'BAZ'],
                'foo BAZ bar',
            ],
            [
                'foo {{ $var }} bar',
                [],
                'foo  bar',
            ],
            [
                "foo {#coment#} bar",
                [],
                "foo  bar",
            ],
            [
                "foo {# coment\nline 2 #} bar",
                [],
                "foo  bar",
            ],
            [
                "foo {#-coment#} bar",
                [],
                "foo bar",
            ],
            [
                "foo\n {#-coment#} bar",
                [],
                "foo\n bar",
            ],
            [
                "foo /*{#-coment#}*/ bar",
                [],
                "foo */ bar",
            ],
            [
                "foo /*{#--coment#}*/ bar",
                [],
                "foo*/ bar",
            ],
            [
                "foo\n/*{#-coment#}*/ bar",
                [],
                "foo\n*/ bar",
            ],
            [
                "foo\n/*{#--coment#}*/ bar",
                [],
                "foo\n*/ bar",
            ],
            [
                "foo\n  /*{#--coment#}*/ bar",
                [],
                "foo\n*/ bar",
            ],
            [
                "foo {#coment-#} bar",
                [],
                "foo bar",
            ],
            [
                "foo {#coment-#} \nbar",
                [],
                "foo bar",
            ],
            [
                "foo {#coment-#} \n\nbar",
                [],
                "foo \nbar",
            ],
            [
                "foo /*{#coment-#}*/ bar",
                [],
                "foo /* bar",
            ],
            [
                "foo /*{#coment--#}*/ bar",
                [],
                "foo /*bar",
            ],
            [
                "foo /*{#coment-#}*/\nbar",
                [],
                "foo /*\nbar",
            ],
            [
                "foo /*{#coment--#}*/\nbar",
                [],
                "foo /*bar",
            ],
            [
                "foo /*{#coment--#}*/\n\nbar",
                [],
                "foo /*\nbar",
            ],
            [
                "foo /*{#coment--#}*/  \n\nbar",
                [],
                "foo /*\nbar",
            ],
            [
                "foo {#- coment -#} \nbar",
                [],
                "foobar",
            ],
            [
                "foo {#- coment -#} \n bar",
                [],
                "foo bar",
            ],
            [
                "foo {# coment #} bar {# coment\n #} baz",
                [],
                "foo  bar  baz",
            ],
            [
                "foo {#- coment #} bar {# coment\n -#} baz",
                [],
                "foo bar baz",
            ],
            [
                'foo {% if $foo->isInt() %}bar {% endif -%} baz',
                ['foo' => 123],
                "foo bar baz",
            ],
            [
                'foo {% if $foo->isInt() %}bar {% endif -%} baz',
                ['foo' => '123'],
                "foo baz",
            ],
            [
                'foo {%- if $foo->isInt() -%} bar {%- endif -%} baz',
                ['foo' => '123'],
                "foobaz",
            ],
            [
                'foo <!--{% if $foo->isInt() %}--> bar <!--{% endif %}--> baz',
                ['foo' => 123],
                "foo <!----> bar <!----> baz",
            ],
            [
                'foo <!--{%- if $foo->isInt() -%}--> bar <!--{%- endif -%}--> baz',
                ['foo' => 123],
                "foo  bar  baz",
            ],
            [
                'foo <!--{%-- if $foo->isInt() --%}--> bar <!--{%-- endif --%}--> baz',
                ['foo' => 123],
                "foobarbaz",
            ],
            [
                "foo\n<!--{% if \$foo->isInt() %}-->\nbar\n<!--{% endif %}-->\nbaz",
                ['foo' => 123],
                "foo\n<!---->\nbar\n<!---->\nbaz",
            ],
            [
                "foo\n<!--{%- if \$foo->isInt() -%}-->\nbar\n<!--{%- endif -%}-->\nbaz",
                ['foo' => 123],
                "foo\n\nbar\n\nbaz",
            ],
            [
                "foo\n<!--{%-- if \$foo->isInt() --%}-->\nbar\n<!--{%-- endif --%}-->\nbaz",
                ['foo' => 123],
                "foo\nbar\nbaz",
            ],
            [
                "foo\n  <!--{%-- if \$foo->isInt() --%}-->  \nbar\n  <!--{%-- endif --%}-->  \nbaz",
                ['foo' => 123],
                "foo\nbar\nbaz",
            ],
            [
                'foo {% if $foo->isInt() %} bar {% else %} baz {% endif %} qux',
                ['foo' => 123],
                "foo  bar  qux",
            ],
            [
                'foo {% if $foo->isInt() %} bar {% else %} baz {% endif %} qux',
                ['foo' => '123'],
                "foo  baz  qux",
            ],
            [
                'foo {% if $foo->isInt() %} bar {% elseif $foo->isFloat() %} baz {% else %} qux {% endif %} quxx',
                ['foo' => 123],
                "foo  bar  quxx",
            ],
            [
                'foo {% if $foo->isInt() %} bar {% elseif $foo->isFloat() %} baz {% else %} qux {% endif %} quxx',
                ['foo' => 123.456],
                "foo  baz  quxx",
            ],
            [
                'foo {% if $foo->isInt() %} bar {% elseif $foo->isFloat() %} baz {% else %} qux {% endif %} quxx',
                ['foo' => '123.456'],
                "foo  qux  quxx",
            ],
            [
                'foo {% if $foo->isInt() %} bar {% elseif $foo->isFloat() %} baz {% elseif $foo->isArray() %} qux {% endif %} quxx',
                ['foo' => 123],
                "foo  bar  quxx",
            ],
            [
                'foo {% if $foo->isInt() %} bar {% elseif $foo->isFloat() %} baz {% elseif $foo->isArray() %} qux {% endif %} quxx',
                ['foo' => 123.45],
                "foo  baz  quxx",
            ],
            [
                'foo {% if $foo->isInt() %} bar {% elseif $foo->isFloat() %} baz {% elseif $foo->isArray() %} qux {% endif %} quxx',
                ['foo' => [123]],
                "foo  qux  quxx",
            ],
            [
                'foo {% if $foo->isInt() %} bar {% elseif $foo->isFloat() %} baz {% elseif $foo->isArray() %} qux {% endif %} quxx',
                ['foo' => '123'],
                "foo  quxx",
            ],
            [
                'foo {% if $foo %} bar {% if $bar %} baz {% endif %} qux {% endif %} quxx',
                ['foo' => true, 'bar' => true],
                "foo  bar  baz  qux  quxx",
            ],
            [
                'foo {% if $foo %} bar {% if $bar %} baz {% endif %} qux {% endif %} quxx',
                ['foo' => true, 'bar' => false],
                "foo  bar  qux  quxx",
            ],
            [
                'foo {% if $foo %} bar {% if $bar %} baz {% endif %} qux {% endif %} quxx',
                ['foo' => false, 'bar' => false],
                "foo  quxx",
            ],
            [
                'foo {% if $foo %} bar {% if $bar %} baz {% endif %} qux {% endif %} quxx',
                ['foo' => false, 'bar' => true],
                "foo  quxx",
            ],
            [
                'a {% for $list as $value %}{{$value}}{% endfor %} b',
                [],
                "a  b",
            ],
            [
                'a {% for $list as $value %}{{$value}}{% else %}(no data){% endfor %} b',
                [],
                "a (no data) b",
            ],
            [
                'a {% for $list as $value %}{{$value}}{% endfor %} b',
                ['list' => []],
                "a  b",
            ],
            [
                'a {% for $list as $value %}{{$value}}{% endfor %} b',
                ['list' => ['a', 'b', 'c']],
                "a abc b",
            ],
            [
                'a {% for $list as $value %}{{$value}}{% else %}(no data){% endfor %} b',
                ['list' => ['a', 'b', 'c']],
                "a abc b",
            ],
            [
                'a {% for $list as $key => $value %} {{$key}}={{$value}}({{$bar}}) {% endfor %} b',
                ['list' => ['a', 'b', 'c'], 'bar' => 'bar'],
                "a  0=a(bar)  1=b(bar)  2=c(bar)  b",
            ],
            [
                'a {% for $a as $i => $av %}{% for $b as $j => $bv %}{{$i}}:{{$av}}-{{$j}}:{{$bv}} {% endfor %}{% endfor %}b',
                ['a' => ['a', 'b', 'c'], 'b' => ['a' => 1, 'b' => 2]],
                "a 0:a-a:1 0:a-b:2 1:b-a:1 1:b-b:2 2:c-a:1 2:c-b:2 b",
            ],
            [
                'a {{$foo}} b',
                ['foo' => 'f'],
                "a f b",
            ],
            [
                'a {{-$foo-}} b',
                ['foo' => 'f'],
                "afb",
            ],
            [
                'a <!--{{$foo}}--> b',
                ['foo' => 'f'],
                "a <!--f--> b",
            ],
            [
                'a <!--{{-$foo-}}--> b',
                ['foo' => 'f'],
                "a f b",
            ],
            [
                'a <!--{{-- $foo --}}--> b',
                ['foo' => 'f'],
                "afb",
            ],
            [
                "a\n<!--{{ \$foo }}-->\nb",
                ['foo' => 'f'],
                "a\n<!--f-->\nb",
            ],
            [
                "a\n<!--{{- \$foo -}}-->\nb",
                ['foo' => 'f'],
                "a\nf\nb",
            ],
            [
                "a\n<!--{{-- \$foo --}}-->\nb",
                ['foo' => 'f'],
                "a\nfb",
            ],
            [
                "a\n  <!--{{-- \$foo --}}-->  \nb",
                ['foo' => 'f'],
                "a\nfb",
            ],
        ];
    }

    /**
     * @dataProvider dataRenders
     */
    public function test_render(?string $text, array $vars, $expect)
    {
        if ($expect instanceof \Exception) {
            $this->expectException(get_class($expect));
            $this->expectExceptionMessage($expect->getMessage());
        }
        $this->assertSame($expect, Letterpress::of($text)->with($vars)->render());
    }

    public function test_defined()
    {
        $this->assertTrue(Letterpress::defined('if'));
        $this->assertTrue(Letterpress::defined('elseif'));
        $this->assertFalse(Letterpress::defined('env'));
    }

    public function test_block()
    {
        $this->assertFalse(Letterpress::defined('upper'));

        Letterpress::block('upper', null, function (array $nodes, array $vars) {
            foreach ($nodes as $node) {
                return strtoupper(Letterpress::process($node['nodes'], $vars));
            }
        });

        $this->assertTrue(Letterpress::defined('upper'));
        $this->assertSame('foo BAR baz', Letterpress::of('foo {% upper %}bar{% endupper %} baz')->render());
    }

    public function test_filter()
    {
        $this->assertFalse(Letterpress::defined('upper'));

        Letterpress::filter('upper', function (string $body) {
            return strtoupper($body);
        });

        $this->assertTrue(Letterpress::defined('upper'));
        $this->assertSame('foo BAR baz', Letterpress::of('foo {% upper %}bar{% endupper %} baz')->render());

        Letterpress::filter('filter', function (string $body, $filter) {
            return Reflector::evaluate($filter, array_merge([$body]));
        });
        $this->assertSame('foo BAR baz', Letterpress::of('foo {% filter "strtoupper" %}bar{% endfilter %} baz')->render());

        Letterpress::filter('replace', function (string $body, $pattern, $replacement, int $limit = -1) {
            return preg_replace($pattern, $replacement, $body, $limit);
        });
        $this->assertSame('foo bAr baz', Letterpress::of('foo {% replace "/a/", "A" %}bar{% endreplace %} baz')->render());
    }

    public function test_clear()
    {
        $this->assertTrue(Letterpress::defined('if'));
        $this->assertTrue(Letterpress::defined('for'));
        $this->assertFalse(Letterpress::defined('upper'));

        Letterpress::filter('upper', function (string $body) {
            return strtoupper($body);
        });

        $this->assertTrue(Letterpress::defined('if'));
        $this->assertTrue(Letterpress::defined('for'));
        $this->assertTrue(Letterpress::defined('upper'));

        Letterpress::clear();

        $this->assertTrue(Letterpress::defined('if'));
        $this->assertTrue(Letterpress::defined('for'));
        $this->assertFalse(Letterpress::defined('upper'));
    }

    public function test_block_duplicatedTag()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Tag 'if' is already defined.");

        Letterpress::block('if', null, function (array $nodes, array $vars) {});
    }

    public function test_block_unavailableSiblingTag()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Tag 'foo' contains unavailable sibling tags [if, for], these are already defined as tag.");

        Letterpress::block('foo', ['foo' => ['if', 'bar', 'for'], 'if' =>['for'], 'bar' => [], 'for' => []], function (array $nodes, array $vars) {});
    }

    public function test_embed()
    {
        $this->assertFalse(Letterpress::defined('hello'));

        Letterpress::embed('hello', function (array $node, array $vars) {
            return trim("'Hello ".Letterpress::evaluate($node['code'], $vars))."'";
        });

        $this->assertTrue(Letterpress::defined('hello'));
        $this->assertSame("foo 'Hello' baz", Letterpress::of('foo {% hello %} baz')->render());
        $this->assertSame("foo 'Hello World' baz", Letterpress::of('foo {% hello "World" %} baz')->render());
        $this->assertSame("foo 'Hello Rebet' baz", Letterpress::of('foo {% hello $name %} baz')->with(['name' => 'Rebet'])->render());
        $text = Letterpress::of('foo {%if $name %}*{% hello $name %}*{% else %}{%hello "Default" %}{%endif%} baz');
        $this->assertSame("foo *'Hello Rebet'* baz", $text->with(['name' => 'Rebet'])->render());
        $this->assertSame("foo 'Hello Default' baz", $text->with(['name' => null])->render());
    }

    public function test_embed_duplicatedTag()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Tag 'if' is already defined.");

        Letterpress::embed('if', function (array $nodes, array $vars) {});
    }

    public function test_function()
    {
        $this->assertFalse(Letterpress::defined('hello'));

        Letterpress::function('hello', function (string $name = '') {
            return trim("'Hello {$name}")."'";
        });

        $this->assertTrue(Letterpress::defined('hello'));
        $this->assertSame("foo 'Hello' baz", Letterpress::of('foo {% hello %} baz')->render());
        $this->assertSame("foo 'Hello World' baz", Letterpress::of('foo {% hello "World" %} baz')->render());
        $this->assertSame("foo 'Hello Rebet' baz", Letterpress::of('foo {% hello $name %} baz')->with(['name' => 'Rebet'])->render());
        $text = Letterpress::of('foo {%if $name %}*{% hello $name %}*{% else %}{%hello "Default" %}{%endif%} baz');
        $this->assertSame("foo *'Hello Rebet'* baz", $text->with(['name' => 'Rebet'])->render());
        $this->assertSame("foo 'Hello Default' baz", $text->with(['name' => null])->render());


        Letterpress::function('welcome', function () {
            return "Welcome ".(Auth::user()->isGuest() ? 'to Rebet' : Auth::user()->name)."!";
        });
        $this->signout();
        $this->assertSame("Welcome to Rebet!", Letterpress::of('{% welcome %}')->render());
        $this->signin();
        $this->assertSame("Welcome User!", Letterpress::of('{% welcome %}')->render());
    }

    public function test_if()
    {
        $this->assertFalse(Letterpress::defined('env'));

        Letterpress::if('env', function (string ...$env) {
            return App::envIn(...$env);
        });

        $this->assertTrue(Letterpress::defined('env'));
        $this->assertSame('a b c', Letterpress::of('a {% env "unittest" %}b{% endenv %} c')->render());
        $this->assertSame('a b d', Letterpress::of('a {% env "unittest", "development" %}b{% else %}c{% endenv %} d')->render());
        $this->assertSame('a c d', Letterpress::of('a {% env "development" %}b{% else %}c{% endenv %} d')->render());
        $this->assertSame('a c e', Letterpress::of('a {% env "development" %}b{% elseenv "unittest" %}c{% else %}d{% endenv %} e')->render());
        $this->assertSame('a d e', Letterpress::of('a {% env "development" %}b{% elseenv "production" %}c{% else %}d{% endenv %} e')->render());
    }

    public function test_jsonSerialize()
    {
        $this->assertSame([], Letterpress::of('')->jsonSerialize());
        $this->assertSame(['foo' => 1], Letterpress::of('')->with(['foo' => 1])->jsonSerialize());
    }
}
