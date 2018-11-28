<?php
namespace Rebet\Routing;

use Rebet\Auth\Auth;
use Rebet\Http\Request;
use Rebet\Http\Response;
use Rebet\Validation\ValidData;
use Rebet\View\View;

/**
 * Controller class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
abstract class Controller
{
    /**
     * Request
     *
     * @var Request
     */
    public $request = null;

    /**
     * Route
     *
     * @var Route
     */
    public $route = null;
    
    /**
     * Perform preprocessing of the controller.
     * Please override with subclass if necessary.
     *
     * @param Request $request
     * @return Request
     */
    public function before(Request $request) : Request
    {
        return $request;
    }

    /**
     * Perform postprocessing of the controller.
     * Please override with subclass if necessary.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function after(Request $request, Response $response) : Response
    {
        return $response;
    }

    /**
     * Validate input data by given rules.
     *
     * @param string $crud
     * @param string|Rule|array $rules
     * @param string $fallback_url
     * @return ValidData
     */
    protected function validate(string $crud, $rules, string $fallback_url) : ValidData
    {
        return $this->request->validate($crud, $rules, $fallback_url);
    }

    /**
     * Get the default (or given name) view.
     *
     * @param string|null $name (default: default view of current route)
     * @param bool $apply_change (default: true)
     * @return View
     */
    protected function view(?string $name = null, bool $apply_change = true) : View
    {
        $selector = new ViewSelector($this->request, Auth::user());
        return $selector->view($name, $apply_change);
    }
}
