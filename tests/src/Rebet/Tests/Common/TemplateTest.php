<?php
namespace Rebet\Tests\Common;

use Rebet\Common\Exception\LogicException;
use Rebet\Common\Template;
use Rebet\Tests\RebetTestCase;

class TemplateTest extends RebetTestCase
{
    protected function setUp() : void
    {
        parent::setUp();
    }

    public function dataCompiles() : array
    {
        return [
            [
                "",
                ['tag' => '', 'code' => '', 'nodes' => []]
            ],
            [
                "foo",
                ['tag' => '', 'code' => '', 'nodes' => [
                    'foo'
                ]]
            ],
            [
                "foo {{ \$var }} bar",
                ['tag' => '', 'code' => '', 'nodes' => [
                    "foo {{ \$var }} bar"
                ]]
            ],
            [
                "foo {#coment-#} bar",
                ['tag' => '', 'code' => '', 'nodes' => [
                    "foo bar"
                ]]
            ],
            [
                "foo {#coment#} \nbar",
                ['tag' => '', 'code' => '', 'nodes' => [
                    "foo  \nbar"
                ]]
            ],
            [
                "foo {#-coment#} \nbar",
                ['tag' => '', 'code' => '', 'nodes' => [
                    "foo \nbar"
                ]]
            ],
            [
                "foo {#coment-#} \nbar",
                ['tag' => '', 'code' => '', 'nodes' => [
                    "foo bar"
                ]]
            ],
            [
                "foo {#- coment -#} \nbar",
                ['tag' => '', 'code' => '', 'nodes' => [
                    "foobar"
                ]]
            ],
            [
                "foo {#- coment -#} \n bar",
                ['tag' => '', 'code' => '', 'nodes' => [
                    "foo bar"
                ]]
            ],
            [
                "foo {#- coment -#} \n\n bar",
                ['tag' => '', 'code' => '', 'nodes' => [
                    "foo\n bar"
                ]]
            ],
            [
                "foo {# coment #} bar {# coment\n #} baz",
                ['tag' => '', 'code' => '', 'nodes' => [
                    "foo  bar  baz"
                ]]
            ],
            [
                "foo {#- coment #} bar {# coment\n -#} baz",
                ['tag' => '', 'code' => '', 'nodes' => [
                    "foo bar baz"
                ]]
            ],
            [
                "foo {% if \$user->isAdmin() %} bar {% endif %} baz",
                ['tag' => '', 'code' => '', 'nodes' => [
                    "foo ",
                    ['tag' => "if", 'code' => "\$user->isAdmin()", 'nodes' => [
                        " bar "
                    ]],
                    " baz",
                ]]
            ],
            [
                "foo {%- if \$user->isAdmin() -%} bar {%- endif -%} baz",
                ['tag' => '', 'code' => '', 'nodes' => [
                    "foo",
                    ['tag' => "if", 'code' => "\$user->isAdmin()", 'nodes' => [
                        "bar"
                    ]],
                    "baz",
                ]]
            ],
            [
                "foo {% if \$user->isAdmin() %} bar {% else %} baz {% endif %} qux",
                ['tag' => '', 'code' => '', 'nodes' => [
                    "foo ",
                    ['tag' => "if", 'code' => "\$user->isAdmin()", 'nodes' => [
                        " bar "
                    ]],
                    ['tag' => "else", 'code' => '', 'nodes' => [
                        " baz "
                    ]],
                    " qux",
                ]]
            ],
            [
                "foo {% if \$user->isAdmin() %} bar {% elseif \$user->isMember() %} baz {% else %} qux {% endif %} quxx",
                ['tag' => '', 'code' => '', 'nodes' => [
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
                ]]
            ],
            [
                "foo {% if \$user->isAdmin() %} bar {% elseif \$user->isMember() %} baz {% elseif \$user->isGuest() %} qux {% endif %} quxx",
                ['tag' => '', 'code' => '', 'nodes' => [
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
                ]]
            ],
            [
                "foo {% if true %} bar {% if false %} baz {% endif %} qux {% endif %} quxx",
                ['tag' => '', 'code' => '', 'nodes' => [
                    "foo ",
                    ['tag' => "if", 'code' => "true", 'nodes' => [
                        " bar ",
                        ['tag' => "if", 'code' => "false", 'nodes' => [
                            " baz ",
                        ]],
                        " qux ",
                    ]],
                    " quxx",
                ]]
            ],
            [
                "a{% if true %}b{% if false %}c{% else %}d{% endif %}e{% else %}f{% endif %}g",
                ['tag' => '', 'code' => '', 'nodes' => [
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
                ]]
            ],
            [
                'a {% for $list as $key => $value %} {{$key}} = {{$value}} {% endfor %} b',
                ['tag' => '', 'code' => '', 'nodes' => [
                    "a ",
                    ['tag' => "for", 'code' => '$list as $key => $value', 'nodes' => [
                        ' {{$key}} = {{$value}} ',
                    ]],
                    " b",
                ]]
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
    public function testCompile(string $text, $expect)
    {
        if ($expect instanceof \Exception) {
            $this->expectException(get_class($expect));
            $this->expectExceptionMessage($expect->getMessage());
        }
        $template = new Template();
        $this->assertSame($expect, $this->invoke($template, 'compile', [$text, Template::config('tags', false, [])]));
    }
}
