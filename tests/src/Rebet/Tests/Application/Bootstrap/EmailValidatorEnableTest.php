<?php
namespace Rebet\Tests\Application\Bootstrap;

use Rebet\Application\Bootstrap\EmailValidatorEnable;
use Rebet\Application\Kernel;
use Rebet\Mail\Validator\EmailValidator;
use Rebet\Tests\RebetTestCase;
use Rebet\Tools\Reflection\Reflector;
use Symfony\Component\Mime\Address;

class EmailValidatorEnableTest extends RebetTestCase
{
    private $validator_backup;
    private $original_validator_backup;

    protected function setUp() : void
    {
        parent::setUp();
        // App::init() (triggered by parent::setUp()) already runs this same bootstrapper via the
        // kernel's bootstrapper list, so Address::$validator and EmailValidator's own
        // original_validator (both static state that outlives a single test) are backed up here
        // and restored in tearDown() to keep this test isolated from others.
        $this->validator_backup          = Reflector::get(Address::class, 'validator', null, true);
        $this->original_validator_backup = Reflector::get(EmailValidator::class, 'original_validator', null, true);
    }

    protected function tearDown() : void
    {
        $address_class = Address::class;
        Reflector::set($address_class, 'validator', $this->validator_backup, true);
        $validator_class = EmailValidator::class;
        Reflector::set($validator_class, 'original_validator', $this->original_validator_backup, true);
        parent::tearDown();
    }

    public function test_bootstrap()
    {
        // App::init() already ran this bootstrapper once, so force back to the pre-enable state
        // first in order to verify bootstrap() actually installs the validator itself.
        EmailValidator::reset();
        $this->assertNotInstanceOf(EmailValidator::class, Reflector::get(Address::class, 'validator', null, true));

        $kernel       = $this->createMock(Kernel::class);
        $bootstrapper = new EmailValidatorEnable();
        $bootstrapper->bootstrap($kernel);

        $this->assertInstanceOf(EmailValidator::class, Reflector::get(Address::class, 'validator', null, true));
    }
}
