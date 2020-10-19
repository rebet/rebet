<?php
namespace Rebet\Mail\Mime;

use Rebet\Tools\Template\Renderable;
use Swift_Message;
use Swift_Mime_SimpleMimeEntity;

/**
 * Mime Message class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2020 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class MimeMessage extends Swift_Message
{
    /**
     * {@inheritDoc}
     */
    public function getBody()
    {
        $body = parent::getBody();
        return parent::convertString($body instanceof Renderable ? $body->render() : (string)$body);
    }

    /**
     * {@inheritDoc}
     */
    protected function convertString($string)
    {
        return $string ;
    }

    /**
     * {@inheritDoc}
     */
    public function addPart($body, $contentType = null, $charset = null)
    {
        return $this->attach((new MimePart($body, $contentType, $charset))->setEncoder($this->getEncoder()));
    }

    /**
     * Get part of given index.
     *
     * @param int $index (default: 0)
     * @return Swift_Mime_SimpleMimeEntity|null
     */
    public function getPart(int $index = 0) : ?Swift_Mime_SimpleMimeEntity
    {
        return $this->getChildren()[$index] ?? null ;
    }

    /**
     * Get a loggable string.
     *
     * @return string
     */
    public function toReadableString() : string
    {
        return static::convertToReadableString($this);
    }

    /**
     * Get a loggable string out of a Swiftmailer entity.
     *
     * @param  \Swift_Mime_SimpleMimeEntity  $entity
     * @return string
     */
    public static function convertToReadableString(Swift_Mime_SimpleMimeEntity $entity) : string
    {
        $string = HeaderSet::convertToReadableString($entity->getHeaders())."\r\n".$entity->getBody();
        foreach ($entity->getChildren() as $child) {
            $string .= "\r\n\r\n".static::convertToReadableString($child);
        }
        return $string;
    }
}
