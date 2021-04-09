<?php
namespace Rebet\Mail\Validator\Parser;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Exception\ConsecutiveDot;
use Egulias\EmailValidator\Exception\DotAtEnd;
use Egulias\EmailValidator\Exception\DotAtStart;
use Egulias\EmailValidator\Exception\UnopenedComment;
use Egulias\EmailValidator\Parser\LocalPart;
use Egulias\EmailValidator\Warning\LocalTooLong;

/**
 * Loose Local Part Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2020 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class LooseLocalPart extends LocalPart
{
    /**
     * @var string[] errors you want to ignore (that can be included DotAtStart::class, ConsecutiveDot::class and DotAtEnd::class).
     */
    protected $ignores;

    /**
     * Create Loose Local Part Parser.
     *
     * @param EmailLexer $lexer
     * @param string[] $ignores errors you want to ignore (that can be included DotAtStart::class, ConsecutiveDot::class and DotAtEnd::class).
     */
    public function __construct(EmailLexer $lexer, array $ignores = [])
    {
        parent::__construct($lexer);
        $this->ignores = $ignores;
    }

    /**
     * Throw the given exception of InvalidEmail, but if the given exception is included in ignore list, then the exception register warnings and not throw.
     *
     * @param string $exception class name of InvalidEmail subclass
     * @return void
     */
    protected function throw(string $exception) : void
    {
        if (!in_array($exception, $this->ignores)) {
            throw new $exception();
        }
        $this->warnings[$exception::CODE] = new $exception();
    }

    /**
     * {@inheritDoc}
     */
    public function parse($localPart)
    {
        $parseDQuote       = true;
        $closingQuote      = false;
        $openedParenthesis = 0;
        $totalLength       = 0;

        while ($this->lexer->token['type'] !== EmailLexer::S_AT && null !== $this->lexer->token['type']) {
            if ($this->lexer->token['type'] === EmailLexer::S_DOT && null === $this->lexer->getPrevious()['type']) {
                $this->throw(DotAtStart::class);
            }

            $closingQuote = $this->checkDQUOTE($closingQuote);
            if ($closingQuote && $parseDQuote) {
                $parseDQuote = $this->parseDoubleQuote();
            }

            if ($this->lexer->token['type'] === EmailLexer::S_OPENPARENTHESIS) {
                $this->parseComments();
                $openedParenthesis += $this->getOpenedParenthesis();
            }

            if ($this->lexer->token['type'] === EmailLexer::S_CLOSEPARENTHESIS) {
                if ($openedParenthesis === 0) {
                    throw new UnopenedComment();
                }

                $openedParenthesis--;
            }

            $this->checkConsecutiveDots();

            if ($this->lexer->token['type'] === EmailLexer::S_DOT &&
                $this->lexer->isNextToken(EmailLexer::S_AT)
            ) {
                $this->throw(DotAtEnd::class);
            }

            $this->warnEscaping();
            $this->isInvalidToken($this->lexer->token, $closingQuote);

            if ($this->isFWS()) {
                $this->parseFWS();
            }

            $totalLength += strlen($this->lexer->token['value']);
            $this->lexer->moveNext();
        }

        if ($totalLength > LocalTooLong::LOCAL_PART_LENGTH) {
            $this->warnings[LocalTooLong::CODE] = new LocalTooLong();
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function checkConsecutiveDots()
    {
        if ($this->lexer->token['type'] === EmailLexer::S_DOT && $this->lexer->isNextToken(EmailLexer::S_DOT)) {
            $this->throw(ConsecutiveDot::class);
        }
    }

    /**
     * Get ignore InvalidEmail subclass names.
     *
     * @return string[] $ignores name that subclass of InvalidEmail exception.
     */
    public function ignores() : array
    {
        return $this->ignores;
    }
}
