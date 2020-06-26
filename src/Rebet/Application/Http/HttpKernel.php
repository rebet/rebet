<?php
namespace Rebet\Application\Http;

use Rebet\Application\Bootstrap\LoadApplicationConfiguration;
use Rebet\Application\Bootstrap\LoadEnvironmentVariables;
use Rebet\Application\Bootstrap\LoadFrameworkConfiguration;
use Rebet\Application\Kernel as ApplicationKernel;
use Rebet\Application\Structure;
use Rebet\Http\Request;
use Rebet\Http\Response;
use Rebet\Routing\Router;

/**
 * HTTP Kernel Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
abstract class HttpKernel extends ApplicationKernel
{
    /**
     * {@inheritDoc}
     *
     * @param Structure $structure
     * @param string $channel (default: 'web')
     */
    public function __construct(Structure $structure, string $channel = 'web')
    {
        parent::__construct($structure, $channel);
    }

    /**
     * {@inheritDoc}
     */
    protected function bootstrappers() : array
    {
        return [
            LoadEnvironmentVariables::class,
            LoadFrameworkConfiguration::class,
            LoadApplicationConfiguration::class,
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @param Request $input
     * @param null $output do not use in this class (default: null)
     * @return Response
     */
    public function handle($input, $output = null)
    {
        return Router::handle($input);
    }

    /**
     * Run an action by name.
     *
     * @param string $action
     * @param array $parameters (default: [])
     * @param null $output do not use in this class (default: null)
     * @return Response
     */
    public function call(string $action, array $parameters = [], $output = null)
    {
        return Router::handle(Request::create($action, 'GET', $parameters));
    }

    /**
     * Terminate the application.
     *
     * @param Request $input
     * @param Response $result
     * @return void
     */
    public function terminate($input, $result) : void
    {
        Router::terminate($input, $result);
    }
}
