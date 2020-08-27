<?php
namespace Rebet\Application\Http;

use Rebet\Application\Bootstrap\HandleExceptions;
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
     * Current handling request.
     *
     * @var Request
     */
    protected $request;

    /**
     * Current handling response.
     *
     * @var Response
     */
    protected $response;

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
            HandleExceptions::class,
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @param Request|null $input (default: null for Request::createFromGlobals())
     * @param null $output do not use in this class (default: null)
     * @return Response
     */
    public function handle($input = null, $output = null)
    {
        return $this->response = Router::handle($this->request = $input ?? Request::createFromGlobals());
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
        return $this->response = Router::handle($this->request = Request::create($action, 'GET', $parameters));
    }

    /**
     * Terminate the application.
     *
     * @return void
     */
    public function terminate() : void
    {
        Router::terminate($this->request ?? $this->request = Request::createFromGlobals(), $this->response);
    }

    /**
     * {@inheritDoc}
     */
    public function fallback(\Throwable $e) : int
    {
        $this->response = $this->exceptionHandler()->handle($this->request ?? $this->request = Request::createFromGlobals(), null, $e);
        $this->response->send();
        return 0;
    }

    /**
     * {@inheritDoc}
     */
    public function report(\Throwable $e) : void
    {
        $this->exceptionHandler()->report($this->request ?? $this->request = Request::createFromGlobals(), $this->response, $e);
    }
}
