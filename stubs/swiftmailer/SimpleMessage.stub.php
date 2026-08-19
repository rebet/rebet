<?php

/**
 * PHPStan stub correcting Swift_Mime_SimpleMessage header getters' return types.
 *
 * The real vendor docblocks claim non-nullable `string`/`array` returns, but every one of these
 * getters delegates to getHeaderFieldModel(), which returns null when the header was never set
 * (e.g. on a freshly constructed message). Rebet\Mail\Mail relies on that null to fall back to [].
 *
 * @see \Rebet\Mail\Mail::sender()
 * @see \Rebet\Mail\Mail::returnPath()
 * @see \Rebet\Mail\Mail::to()
 * @see \Rebet\Mail\Mail::cc()
 * @see \Rebet\Mail\Mail::bcc()
 * @see \Rebet\Mail\Mail::replyTo()
 * @see \Rebet\Mail\Mail::dispositionNotificationTo()
 */
class Swift_Mime_SimpleMessage
{
    /**
     * @return string|array<string,string|null>|null
     */
    public function getReturnPath()
    {
    }

    /**
     * @return string|array<string,string|null>|null
     */
    public function getSender()
    {
    }

    /**
     * @return string|array<string,string|null>|null
     */
    public function getReplyTo()
    {
    }

    /**
     * @return array<string,string|null>|null
     */
    public function getTo()
    {
    }

    /**
     * @return array<string,string|null>|null
     */
    public function getCc()
    {
    }

    /**
     * @return array<string,string|null>|null
     */
    public function getBcc()
    {
    }

    /**
     * @return string|array<string,string|null>|null
     */
    public function getReadReceiptTo()
    {
    }
}
