<?php
namespace Rebet\Http;

use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

/**
 * Redirect Response Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class RedirectResponse extends SymfonyRedirectResponse implements Response
{
    use Respondable;

    /**
     * Create Redirect Response
     *
     * @param string $url
     * @param integer $status
     * @param array $headers
     */
    public function __construct(string $url, int $status = 302, array $headers = [])
    {
        parent::__construct($url, $status, $headers);
    }
}
