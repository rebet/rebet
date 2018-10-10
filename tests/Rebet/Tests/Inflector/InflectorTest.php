<?php
namespace Rebet\Tests\Inflector;

use Rebet\Tests\RebetTestCase;
use Rebet\Inflector\Inflector;

class InflectorTest extends RebetTestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    public function test_pascalize()
    {
        $this->assertNull(Inflector::pascalize(null));
        $this->assertSame('', Inflector::pascalize(''));
        $this->assertSame('PascalCase', Inflector::pascalize('PascalCase'));
        $this->assertSame('CamelCase', Inflector::pascalize('camelCase'));
        $this->assertSame('SnakeCase', Inflector::pascalize('snake_case'));
        $this->assertSame('KebabCase', Inflector::pascalize('kebab-case'));
        $this->assertSame('HumanReadableCase', Inflector::pascalize('Human Readable Case'));
    }

    public function test_camelize()
    {
        $this->assertNull(Inflector::camelize(null));
        $this->assertSame('', Inflector::camelize(''));
        $this->assertSame('pascalCase', Inflector::camelize('PascalCase'));
        $this->assertSame('camelCase', Inflector::camelize('camelCase'));
        $this->assertSame('snakeCase', Inflector::camelize('snake_case'));
        $this->assertSame('kebabCase', Inflector::camelize('kebab-case'));
        $this->assertSame('humanReadableCase', Inflector::camelize('Human Readable Case'));
    }

    public function test_snakize()
    {
        $this->assertNull(Inflector::snakize(null));
        $this->assertSame('', Inflector::snakize(''));
        $this->assertSame('pascal_case', Inflector::snakize('PascalCase'));
        $this->assertSame('camel_case', Inflector::snakize('camelCase'));
        $this->assertSame('snake_case', Inflector::snakize('snake_case'));
        $this->assertSame('kebab_case', Inflector::snakize('kebab-case'));
        $this->assertSame('human_readable_case', Inflector::snakize('Human Readable Case'));
    }

    public function test_kebabize()
    {
        $this->assertNull(Inflector::kebabize(null));
        $this->assertSame('', Inflector::kebabize(''));
        $this->assertSame('pascal-case', Inflector::kebabize('PascalCase'));
        $this->assertSame('camel-case', Inflector::kebabize('camelCase'));
        $this->assertSame('snake-case', Inflector::kebabize('snake_case'));
        $this->assertSame('kebab-case', Inflector::kebabize('kebab-case'));
        $this->assertSame('human-readable-case', Inflector::kebabize('Human Readable Case'));
    }

    public function test_humanize()
    {
        $this->assertNull(Inflector::humanize(null));
        $this->assertSame('', Inflector::humanize(''));
        $this->assertSame('Pascal Case', Inflector::humanize('PascalCase'));
        $this->assertSame('Camel Case', Inflector::humanize('camelCase'));
        $this->assertSame('Snake Case', Inflector::humanize('snake_case'));
        $this->assertSame('Kebab Case', Inflector::humanize('kebab-case'));
        $this->assertSame('Human Readable Case', Inflector::humanize('Human Readable Case'));
    }

    public function test_capitalize()
    {
        $this->assertNull(Inflector::capitalize(null));
        $this->assertSame('', Inflector::capitalize(''));
        $this->assertSame('PascalCase', Inflector::capitalize('PascalCase'));
        $this->assertSame('CamelCase', Inflector::capitalize('camelCase'));
        $this->assertSame('Snake_case', Inflector::capitalize('snake_case'));
        $this->assertSame('Kebab-case', Inflector::capitalize('kebab-case'));
        $this->assertSame('Human Readable Case', Inflector::capitalize('Human Readable Case'));
    }

    public function test_tableize()
    {
        $this->assertNull(Inflector::tableize(null));
        $this->assertSame('', Inflector::tableize(''));
        $this->assertSame('pascal_cases', Inflector::tableize('PascalCase'));
        $this->assertSame('camel_cases', Inflector::tableize('camelCase'));
        $this->assertSame('snake_cases', Inflector::tableize('snake_case'));
        $this->assertSame('kebab_cases', Inflector::tableize('kebab-case'));
        $this->assertSame('human_readable_cases', Inflector::tableize('Human Readable Case'));
    }

    public function test_classify()
    {
        $this->assertNull(Inflector::classify(null));
        $this->assertSame('', Inflector::classify(''));
        $this->assertSame('PascalCase', Inflector::classify('PascalCases'));
        $this->assertSame('CamelCase', Inflector::classify('camelCases'));
        $this->assertSame('SnakeCase', Inflector::classify('snake_cases'));
        $this->assertSame('KebabCase', Inflector::classify('kebab-cases'));
        $this->assertSame('HumanReadableCase', Inflector::classify('Human Readable Cases'));
    }
}
