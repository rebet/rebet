<?php
namespace Rebet\Routing;

use Rebet\Http\Request;
use Rebet\View\View;
use Rebet\Auth\Auth;

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
