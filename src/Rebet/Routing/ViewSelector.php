<?php
namespace Rebet\Routing;

use Rebet\Auth\Auth;
use Rebet\Auth\AuthUser;
use Rebet\Config\Configurable;
use Rebet\Http\Request;
use Rebet\View\View;

/**
 * View Selector class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class ViewSelector
{
    use Configurable;

    public static function defaultConfig()
    {
        return [
            'changer' => null,
        ];
    }

    /**
     * Request
     *
     * @var Request
     */
    protected $request = null;

    /**
     * Authenticated user
     *
     * @var AuthUser
     */
    protected $user = null;

    /**
     * View changer
     *
     * @var \Closure|null
     */
    protected $changer = null;
    
    /**
     * Create a view selector
     *
     * @param Request|null $request (default: Request::current())
     * @param AuthUser|null $user (default: Auth::user())
     * @param callable|null $changer function($view_name, $request, $user):string to return changed view name (default: depend on configure)
     */
    public function __construct(?Request $request = null, ?AuthUser $user = null, ?callable $changer = null)
    {
        $this->request = $request ?? Request::current();
        $this->user    = $user ?? Auth::user();
        $this->changer = $changer ?? static::config('changer', false) ;
    }

    /**
     * Convert ViewSelector changer to View changer.
     *
     * @param boolean $apply_change
     * @return \Closure|null
     */
    protected function changer(bool $apply_change) : ?\Closure
    {
        if (!$this->changer || !$apply_change) {
            return null;
        }
        return function ($view_name) {
            $changer = \Closure::fromCallable($this->changer);
            return $changer($view_name, $this->request, $this->user);
        };
    }

    /**
     * Get the default (or given name) view.
     *
     * @param string|null $name (default: default view of current route)
     * @param bool $apply_change (default: true)
     * @return View
     */
    public function view(?string $name = null, bool $apply_change = true) : View
    {
        $name    = $name ?? $this->request->route->defaultView();
        $changer = $this->changer($apply_change);
        return new View($name, $changer);
    }
}
