<?php
namespace Rebet\Http\Response;

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
     * @param integer $status
     * @param array $headers
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
        $flash = Session::current()->flash();
        $flash->set('_inherit_input', array_merge(
            $flash->peek('_inherit_input', []),
            $input
        ));
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
        $flash = Session::current()->flash();
        $flash->set('_inherit_errors', array_merge(
            $flash->peek('_inherit_errors', []),
            $errors
        ));
        return $this;
    }
}
