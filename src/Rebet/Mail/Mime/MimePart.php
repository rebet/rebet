<?php
namespace Rebet\Mail\Mime;

use Rebet\Tools\Template\Renderable;
use Swift_MimePart;

/**
 * Mime Part class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2020 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class MimePart extends Swift_MimePart
{
    /**
     * {@inheritDoc}
     */
    public function __construct($body = null, $contentType = null, $charset = null)
    {
        // Swiftmailer is abandoned and its constructor chain internally uses the
        // `call_user_func_array([$this, 'ParentClass::__construct'], ...)` idiom, which
        // is deprecated since PHP 8.2. The call itself still works correctly, so the
        // deprecation notice is suppressed here rather than patching the vendor library.
        @parent::__construct($body, $contentType, $charset);
    }

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
        return $string;
    }
}
