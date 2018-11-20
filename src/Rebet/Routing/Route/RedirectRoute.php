<?php
namespace Rebet\Routing\Route;

use Rebet\Common\Strings;
use Rebet\Http\Request;
use Rebet\Http\Responder;

/**
 * Redirect Route Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class RedirectRoute extends ClosureRoute
{
    /**
     * Destination url of redirect to.
     *
     * @var string
     */
    protected $destination = null;

    /**
     * Http status of redirect
     *
     * @var int
     */
    protected $status = null;

    /**
     * Create a redirect route
     *
     * @param string $uri
     * @param string $destination
     * @param array $query (default: [])
     * @param integer $status (default: 302)
     */
    public function __construct(string $uri, string $destination, array $query = [], int $status = 302)
    {
        parent::__construct([], $uri, function (Request $request) use ($destination, $query, $status) {
            $vars = $request->attributes->all();
            foreach ($vars as $key => $value) {
                $replace = "{{$key}}";
                if (Strings::contains($destination, $replace)) {
                    $destination = str_replace($replace, $value, $destination);
                } else {
                    $query[$key] = $value;
                }
            }
            $destination = preg_replace('/\/?{.+?}/u', '', $destination);
            $destination = Strings::startsWith($destination, '/') ? $request->route->prefix.$destination : $destination ;
            return Responder::redirect($destination, $query, $status);
        });
        $this->destination = $destination;
        $this->status      = $status;
    }

    /**
     * {@inheritDoc}
     */
    public function __toString()
    {
        return parent::__toString()." redirect to {$this->destination}({$this->status})";
    }
}
