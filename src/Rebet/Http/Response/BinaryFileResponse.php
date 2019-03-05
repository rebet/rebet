<?php
namespace Rebet\Http\Response;

use Rebet\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse as SymfonyBinaryFileResponse;

/**
 * Binary File Response Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class BinaryFileResponse extends SymfonyBinaryFileResponse implements Response
{
    use Respondable;

    /**
     * Create Binary File Response
     *
     * @param mixed $file
     * @param integer $status (default: 200)
     * @param array $headers (default: [])
     * @param boolean $public (default: true)
     * @param string $contentDisposition (default: null)
     * @param boolean $autoEtag (default: false)
     * @param boolean $autoLastModified (default: true)
     */
    public function __construct($file, int $status = 200, array $headers = [], bool $public = true, string $contentDisposition = null, bool $autoEtag = false, bool $autoLastModified = true)
    {
        parent::__construct($file, $status, $headers, $public, $contentDisposition, $autoEtag, $autoLastModified);
    }
}
