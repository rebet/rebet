<?php
namespace Rebet\Routing\Route;

use Rebet\Bridge\Renderable;
use Rebet\Http\Request;
use Rebet\Http\Responder;
use Rebet\Routing\RouteNotFoundException;
use Rebet\View\View;
use Rebet\View\ViewRenderFailedException;

/**
 * View Route Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class ViewRoute extends ClosureRoute
{
    /**
     * Create a redirect route
     *
     * @param string $uri
     * @param string $name
     * @param array $args (default: [])
     */
    public function __construct(string $uri, string $name, array $args = [])
    {
        $self = $this;
        parent::__construct([], $uri, function (Request $request) use ($self, $name, $args) {
            $args = array_merge($request->input(), $request->attributes->all(), $args);
            return Responder::toResponse($self->proxy(View::of($name)->with($args)));
        });
    }

    /**
     * Create a view proxy delegated given view
     *
     * @param View $view
     * @return Renderable
     */
    protected function proxy(View $view) : Renderable
    {
        return new class($view) implements Renderable {
            private $view;

            public function __construct($view)
            {
                $this->view = $view;
            }

            public function render() : string
            {
                try {
                    return $this->view->render();
                } catch (ViewRenderFailedException $e) {
                    throw new RouteNotFoundException("View route [{$this->view->name}] not found. An exception occurred while processing the view.", 404, $e);
                }
            }
        };
    }
}
