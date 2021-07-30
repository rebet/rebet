<?php
namespace Rebet\Mail\Validator\Validation;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Result\InvalidEmail;
use Egulias\EmailValidator\Result\Reason\ConsecutiveDot;
use Egulias\EmailValidator\Result\Reason\DotAtEnd;
use Egulias\EmailValidator\Result\Reason\DotAtStart;
use Egulias\EmailValidator\Validation\EmailValidation;
use Egulias\EmailValidator\Warning\Warning;
use Rebet\Mail\Validator\Parser\LooseEmailParser;
use Rebet\Tools\Config\Configurable;

/**
 * Loose RFC Validation Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2020 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class LooseRFCValidation implements EmailValidation
{
    use Configurable;

    public static function defaultConfig()
    {
        return [
            'ignores' => [
                DotAtEnd::class,
                DotAtStart::class,
                ConsecutiveDot::class,
            ]
        ];
    }

    /**
     * @var Warning[]
     */
    protected $warnings = [];

    /**
     * @var InvalidEmail|null
     */
    protected $error;

    /**
     * @var string[] of ignore InvalidEmail exception class names
     */
    protected $ignores = [];

    /**
     * Create loose RFC validation
     *
     * @param array|null $ignores (default: null for depend on configure)
     */
    public function __construct(?array $ignores = null)
    {
        $this->ignores = $ignores ?? static::config('ignores');
    }

    /**
     * {@inheritDoc}
     */
    public function isValid($email, EmailLexer $emailLexer) : bool
    {
        $parser = new LooseEmailParser($emailLexer, $this->ignores);
        $result = $parser->parse((string)$email);
        if($result->isInvalid()) {
            $this->error = $result;
            return false;
        }

        $this->warnings = $parser->getWarnings();
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getError() : ?InvalidEmail
    {
        return $this->error;
    }

    /**
     * {@inheritDoc}
     */
    public function getWarnings() : array
    {
        return $this->warnings;
    }
}
