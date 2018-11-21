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

    public static function defaultConfig() : array
    {
        return [
            'default_changer' => null,
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
     * @param callable|null $changer function($name, $request, $user){...} to return changed view name (default: depend on configure)
     * @param AuthUser|null $user (default: Auth::user())
     * @param Request|null $request (default: Request::current())
     */
    public function __construct(?callable $changer = null, ?AuthUser $user = null, ?Request $request = null)
    {
        $this->changer = $changer ?? static::config('default_changer', false) ;
        $this->user    = $user ?? Auth::user();
        $this->request = $request ?? Request::current();
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
        return function ($name) {
            $changer = \Closure::fromCallable($this->changer);
            return $changer($name, $this->request, $this->user);
        };
    }

    /**
     * Get the default (or given name) view.
     *
     * @param string|null $name (default: request uri without query)
     * @param bool $apply_change (default: true)
     * @return View
     */
    protected function view(?string $name = null, bool $apply_change = true) : View
    {
        $name    = $name ?? $this->request->getRequestPath();
        $changer = $this->changer($apply_change);
        return new View($name, $changer);
    }
}
