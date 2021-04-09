<?php
namespace Rebet\Mail\Validator\Validation;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\EmailParser;
use Egulias\EmailValidator\Exception\ConsecutiveDot;
use Egulias\EmailValidator\Exception\DotAtEnd;
use Egulias\EmailValidator\Exception\DotAtStart;
use Egulias\EmailValidator\Exception\InvalidEmail;
use Egulias\EmailValidator\Validation\EmailValidation;
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
     * @var EmailParser|null
     */
    protected $parser;

    /**
     * @var array
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
    public function isValid($email, EmailLexer $emailLexer)
    {
        $this->parser = new LooseEmailParser($emailLexer, $this->ignores);
        try {
            $this->parser->parse((string)$email);
        } catch (InvalidEmail $invalid) {
            $this->error = $invalid;
            return false;
        }

        $this->warnings = $this->parser->getWarnings();
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * {@inheritDoc}
     */
    public function getWarnings()
    {
        return $this->warnings;
    }
}
