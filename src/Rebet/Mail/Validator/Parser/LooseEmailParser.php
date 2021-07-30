<?php
namespace Rebet\Mail\Validator\Parser;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\EmailParser;
use Egulias\EmailValidator\Result\Result;

/**
 * Loose Email Parser Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2020 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class LooseEmailParser extends EmailParser
{
    /**
     * @var string[] $ignores
     */
    protected $ignores = [];

    /**
     * Create Email Parser.
     *
     * @param EmailLexer $lexer
     * @param array $ignores errors you want to ignore (that can be included DotAtStart::class, ConsecutiveDot::class and DotAtEnd::class).
     */
    public function __construct(EmailLexer $lexer, array $ignores = [])
    {
        parent::__construct($lexer);
        $this->ignores = $ignores;
    }

    /**
     * {@inheritDoc}
     */
    protected function parseLeftFromAt(): Result
    {
        $localPartParser = new LooseLocalPart($this->lexer, $this->ignores);
        $localPartResult = $localPartParser->parse();
        $this->localPart = $localPartParser->localPart();
        $this->warnings = array_merge($localPartParser->getWarnings(), $this->warnings);

        return $localPartResult;
    }

    /**
     * Get ignore Reason names.
     *
     * @return string[] $ignores name that subclass of Reason.
     */
    public function ignores() : array
    {
        return $this->ignores;
    }
}
