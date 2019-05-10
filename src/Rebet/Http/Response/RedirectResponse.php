<?php
namespace Rebet\Http\Response;

use Rebet\Common\Strings;
use Rebet\Http\Request;
use Rebet\Http\Response;
use Rebet\Http\Session\Session;
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
     * @param integer $status (default: 302)
     * @param array $headers (default: [])
     */
    public function __construct(string $url, int $status = 302, array $headers = [])
    {
        parent::__construct($url, $status, $headers);
    }

    /**
     * Set the input data to the redirect.
     *
     * @param array $input
     * @return self
     */
    public function with(array $input) : self
    {
        Session::current()->saveInheritData('input', $input, $this->getTargetUrlWithoutRoutePrefix());
        return $this;
    }

    /**
     * Set the errors data to the redirect.
     *
     * @todo MessageBag
     *
     * @param array $errors
     * @return self
     */
    public function errors(array $errors) : self
    {
        Session::current()->saveInheritData('errors', $errors, $this->getTargetUrlWithoutRoutePrefix());
        return $this;
    }

    /**
     * Get the redirect target url without route prefix.
     *
     * @return string
     */
    protected function getTargetUrlWithoutRoutePrefix() : string
    {
        $request = Request::current();
        return Strings::ltrim($this->getTargetUrl(), $request ? $request->getRoutePrefix() : '', 1);
    }
}
