<?php
namespace Rebet\Mail\Validator\Parser;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Result\Reason\ConsecutiveDot;
use Egulias\EmailValidator\Result\Reason\DotAtEnd;
use Egulias\EmailValidator\Result\Reason\DotAtStart;
use Egulias\EmailValidator\Parser\LocalPart;
use Egulias\EmailValidator\Result\InvalidEmail;
use Egulias\EmailValidator\Result\Result;
use Egulias\EmailValidator\Result\ValidEmail;
use Egulias\EmailValidator\Warning\LocalTooLong;
use Rebet\Mail\Validator\Warning\ConsecutiveDotWarning;
use Rebet\Mail\Validator\Warning\DotAtEndWarning;
use Rebet\Mail\Validator\Warning\DotAtStartWarning;
use Rebet\Tools\Reflection\Reflector;

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
     * @param string[] $ignores error reasons you want to ignore (that can be included DotAtStart::class, ConsecutiveDot::class and DotAtEnd::class).
     */
    public function __construct(EmailLexer $lexer, array $ignores = [])
    {
        parent::__construct($lexer);
        $this->ignores = $ignores;
    }

    /**
     * {@inheritDoc}
     */
    public function parse() : Result
    {
        $this->lexer->startRecording();

        while ($this->lexer->token['type'] !== EmailLexer::S_AT && null !== $this->lexer->token['type']) {
            if (Reflector::invoke($this, 'hasDotAtStart', [], true)) {
                if (!in_array(DotAtStart::class, $this->ignores)) {
                    return new InvalidEmail(new DotAtStart(), $this->lexer->token['value']);
                }
                $this->warnings[DotAtStartWarning::CODE] = new DotAtStartWarning();
            }

            if ($this->lexer->token['type'] === EmailLexer::S_DQUOTE) {
                $dquoteParsingResult = Reflector::invoke($this, 'parseDoubleQuote', [], true);

                //Invalid double quote parsing
                if($dquoteParsingResult->isInvalid()) {
                    return $dquoteParsingResult;
                }
            }

            if ($this->lexer->token['type'] === EmailLexer::S_OPENPARENTHESIS || 
                $this->lexer->token['type'] === EmailLexer::S_CLOSEPARENTHESIS ) {
                $commentsResult = $this->parseComments();

                //Invalid comment parsing
                if($commentsResult->isInvalid()) {
                    return $commentsResult;
                }
            }

            if ($this->lexer->token['type'] === EmailLexer::S_DOT && $this->lexer->isNextToken(EmailLexer::S_DOT)) {
                if (!in_array(ConsecutiveDot::class, $this->ignores)) {
                    return new InvalidEmail(new ConsecutiveDot(), $this->lexer->token['value']);
                }
                $this->warnings[ConsecutiveDotWarning::CODE] = new ConsecutiveDotWarning();
            }

            if ($this->lexer->token['type'] === EmailLexer::S_DOT &&
                $this->lexer->isNextToken(EmailLexer::S_AT)
            ) {
                if (!in_array(DotAtEnd::class, $this->ignores)) {
                    return new InvalidEmail(new DotAtEnd(), $this->lexer->token['value']);
                }
                $this->warnings[DotAtEndWarning::CODE] = new DotAtEndWarning();
            }

            $resultEscaping = Reflector::invoke($this, 'validateEscaping', [], true);
            if ($resultEscaping->isInvalid()) {
                return $resultEscaping;
            }

            $resultToken = $this->validateTokens(false);
            if ($resultToken->isInvalid()) {
                return $resultToken;
            }

            $resultFWS = Reflector::invoke($this, 'parseLocalFWS', [], true);
            if($resultFWS->isInvalid()) {
                return $resultFWS;
            }

            $this->lexer->moveNext();
        }

        $this->lexer->stopRecording();
        $this->localPart = rtrim($this->lexer->getAccumulatedValues(), '@');
        if (strlen($this->localPart) > LocalTooLong::LOCAL_PART_LENGTH) {
            $this->warnings[LocalTooLong::CODE] = new LocalTooLong();
        }

        return new ValidEmail();
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
