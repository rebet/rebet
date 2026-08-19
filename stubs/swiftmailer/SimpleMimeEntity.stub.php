<?php

/**
 * PHPStan stub correcting Swift_Mime_SimpleMimeEntity::getBody()'s return type.
 *
 * The real vendor docblock claims `@return string`, but the implementation just returns
 * whatever was passed to setBody($body) verbatim (setBody() itself accepts `mixed $body`),
 * so any value (e.g. a Rebet\Tools\Template\Renderable instance, as used by Mail::body())
 * can come back out unconverted.
 *
 * @see \Rebet\Mail\Mime\MimeMessage::getBody()
 * @see \Rebet\Mail\Mime\MimePart::getBody()
 */
class Swift_Mime_SimpleMimeEntity
{
    /**
     * @return mixed
     */
    public function getBody()
    {
    }
}
