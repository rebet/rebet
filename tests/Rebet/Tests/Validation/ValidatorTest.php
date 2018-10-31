<?php
namespace Rebet\Tests\Validation;

use Rebet\Tests\RebetTestCase;
use Rebet\Validation\Validator;
use Rebet\Foundation\App;
use Rebet\Validation\Valid;
use Rebet\Validation\Context;
use Rebet\Config\Config;
use org\bovigo\vfs\vfsStream;
use Rebet\Tests\Mock\Gender;
use Rebet\Validation\Validations;
use Rebet\Validation\BuiltinValidations;

class ValidatorTest extends RebetTestCase
{
    private $root;

    public function setup()
    {
        parent::setUp();

        Config::application([
            BuiltinValidations::class => [
                'resources_dir' => ['vfs://root/resources'],
                'customs'       => [
                    'Ok' => function (Context $c) {
                        return true;
                    },
                    'Ng' => function (Context $c) {
                        $c->appendError("@The {$c->label} is NG.");
                        return false;
                    },
                ]
            ]
        ]);
    }

    public function test_cunstract()
    {
        $validator = new Validator([]);
        $this->assertInstanceOf(Validator::class, $validator);
    }

}
