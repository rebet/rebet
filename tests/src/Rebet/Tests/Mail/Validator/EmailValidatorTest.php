<?php
namespace Rebet\Tests\Mail\Validator;

use Egulias\EmailValidator\Validation\RFCValidation;
use Rebet\Mail\Validator\EmailValidator;
use Rebet\Mail\Validator\Validation\LooseRFCValidation;
use Rebet\Tests\RebetTestCase;
use Rebet\Tools\Reflection\Reflector;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Exception\RfcComplianceException;

class EmailValidatorTest extends RebetTestCase
{
    private $validator_backup;
    private $original_validator_backup;

    protected function setUp() : void
    {
        parent::setUp();
        // App::init() (triggered by parent::setUp()) runs the EmailValidatorEnable bootstrapper,
        // which calls EmailValidator::enable(). Both Address::$validator and
        // EmailValidator::$original_validator are static properties that outlive a single test,
        // so back them up here and restore them in tearDown() to keep each test isolated.
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

    public function test_defaultConfig()
    {
        $this->assertInstanceOf(RFCValidation::class, EmailValidator::config('validation'));
    }

    public function test_isValid()
    {
        $validator = new EmailValidator();
        $this->assertSame(false, $validator->isValid('.invalid..rfc.@foo.com', new RFCValidation()));
        $this->assertSame(false, $validator->isValid('.invalid..rfc.@foo.com', new LooseRFCValidation()));
        EmailValidator::setValidation(new LooseRFCValidation());
        $this->assertSame(true, $validator->isValid('.invalid..rfc.@foo.com', new RFCValidation()));
        $this->assertSame(true, $validator->isValid('.invalid..rfc.@foo.com', new LooseRFCValidation()));
    }

    public function test_setValidation()
    {
        $this->assertInstanceOf(RFCValidation::class, EmailValidator::config('validation'));

        $loose = new LooseRFCValidation();
        EmailValidator::setValidation($loose);
        $this->assertSame($loose, EmailValidator::config('validation'));
    }

    public function test_enable()
    {
        // The EmailValidatorEnable bootstrapper already calls enable() during App::init(), so
        // reset() first to force back to the pre-enable state.
        EmailValidator::reset();
        $this->assertNotInstanceOf(EmailValidator::class, Reflector::get(Address::class, 'validator', null, true));

        EmailValidator::enable();

        $this->assertInstanceOf(EmailValidator::class, Reflector::get(Address::class, 'validator', null, true));
    }

    public function test_enable_isNoopWhenAlreadyEnabled()
    {
        EmailValidator::enable();
        $enabled = Reflector::get(Address::class, 'validator', null, true);
        $this->assertInstanceOf(EmailValidator::class, $enabled);

        // Calling enable() again while already enabled must not install a new instance.
        EmailValidator::enable();
        $this->assertSame($enabled, Reflector::get(Address::class, 'validator', null, true));
    }

    public function test_reset()
    {
        // The EmailValidatorEnable bootstrapper already calls enable() during App::init(), so
        // reset() first to force back to (and capture) the pre-enable state.
        EmailValidator::reset();
        $before = Reflector::get(Address::class, 'validator', null, true);
        $this->assertNotInstanceOf(EmailValidator::class, $before);

        EmailValidator::enable();
        $this->assertInstanceOf(EmailValidator::class, Reflector::get(Address::class, 'validator', null, true));

        EmailValidator::reset();
        $this->assertNotInstanceOf(EmailValidator::class, Reflector::get(Address::class, 'validator', null, true));
        $this->assertSame($before, Reflector::get(Address::class, 'validator', null, true));
    }

    public function test_reset_isNoopWhenEnableWasNeverCalled()
    {
        // Reset the captured original to null, simulating a state where enable() has never
        // been called, regardless of what earlier tests (or the kernel bootstrap) may have done.
        $class = EmailValidator::class;
        Reflector::set($class, 'original_validator', null, true);

        $before = Reflector::get(Address::class, 'validator', null, true);

        EmailValidator::reset();

        $this->assertSame($before, Reflector::get(Address::class, 'validator', null, true));
        $this->assertNull(Reflector::get($class, 'original_validator', null, true));
    }

    public function test_enable_appliesConfiguredValidationToAddress()
    {
        EmailValidator::setValidation(new RFCValidation());
        EmailValidator::enable();

        $this->expectException(RfcComplianceException::class);
        new Address('.invalid..rfc.@foo.com');
    }

    public function test_enable_appliesConfiguredLooseValidationToAddress()
    {
        EmailValidator::setValidation(new LooseRFCValidation());
        EmailValidator::enable();

        $address = new Address('.invalid..rfc.@foo.com', 'Loose');
        $this->assertSame('.invalid..rfc.@foo.com', $address->getAddress());
    }
}
