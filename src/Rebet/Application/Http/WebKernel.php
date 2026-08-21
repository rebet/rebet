<?php
namespace Rebet\Application\Http;

use Rebet\Application\Bootstrap\Bootstrapper;
use Rebet\Application\Bootstrap\HandleExceptions;
use Rebet\Application\Bootstrap\LetterpressTagCustomizer;
use Rebet\Application\Bootstrap\LoadApplicationConfiguration;
use Rebet\Application\Bootstrap\LoadEnvironmentVariables;
use Rebet\Application\Bootstrap\LoadRoutingConfiguration;
use Rebet\Application\Bootstrap\PropertiesMaskingConfiguration;
use Rebet\Application\Kernel;
use Rebet\Application\Structure;
use Rebet\Http\Request;
use Rebet\Http\Response;
use Rebet\Routing\Router;
use Rebet\Tools\Exception\LogicException;

/**
 * Web Kernel Class
 *
 * @template-extends Kernel<Request, Response>
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
abstract class WebKernel extends Kernel
{
    /**
     * Current handling request.
     *
     * @var Request|null
     */
    protected Request|null $request;

    /**
     * Current handling response.
     *
     * @var Response|null
     */
    protected Response|null $response;

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
     * @return array<int, Bootstrapper|class-string<Bootstrapper>|array<int|string, mixed>>
     */
    protected function bootstrappers() : array
    {
        return [
            LoadEnvironmentVariables::class,
            [PropertiesMaskingConfiguration::class, 'masks' => ['password', 'password_confirm']],
            LoadApplicationConfiguration::class,
            LoadRoutingConfiguration::class,
            HandleExceptions::class,
            LetterpressTagCustomizer::class,
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @param Request|null $input (default: null for Request::createFromGlobals())
     * @return Response
     */
    public function handle($input = null) : Response
    {
        return $this->response = Router::handle($this->request = $input ?? Request::createFromGlobals());
    }

    /**
     * Run an action by name.
     *
     * @param string $action
     * @param array<string, mixed> $parameters (default: [])
     * @return Response
     */
    public function call(string $action, array $parameters = []) : Response
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

    /**
     * Get the HTTP request.
     *
     * @return Request
     */
    public function request() : Request
    {
        if ($this->request === null) {
            throw new LogicException('Request has not been set yet.');
        }
        return $this->request;
    }
}
