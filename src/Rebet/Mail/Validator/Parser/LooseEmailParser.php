<?php
namespace Rebet\Mail\Validator\Parser;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\EmailParser;

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
     * Create Email Parser.
     *
     * @param EmailLexer $lexer
     * @param array $ignores errors you want to ignore (that can be included DotAtStart::class, ConsecutiveDot::class and DotAtEnd::class).
     */
    public function __construct(EmailLexer $lexer, array $ignores = [])
    {
        parent::__construct($lexer);
        $this->localPartParser = new LooseLocalPart($lexer, $ignores);
    }

    /**
     * Get ignore InvalidEmail subclass names.
     *
     * @return string[] $ignores name that subclass of InvalidEmail exception.
     */
    public function ignores() : array
    {
        return $this->localPartParser->ignores();
    }
}
