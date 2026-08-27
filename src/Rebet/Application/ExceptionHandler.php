<?php
namespace Rebet\Application;

/**
 * Exception Handler Class
 *
 * @template I
 * @template R
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
abstract class ExceptionHandler
{
    /**
     * {@inheritDoc}
     */
    public function __construct()
    {
    }

    /**
     * Report an exception.
     * Just only report, this function do not display result.
     *
     * @param I $input
     * @param R|null $result
     * @param \Throwable $e
     * @return void
     */
    abstract public function report($input, $result, \Throwable $e) : void;

    /**
     * Handle an exception
     *
     * @param I|null $input
     * @param \Throwable $e
     * @return R
     */
    abstract public function handle($input, \Throwable $e);

    /**
     * Must be able to invoke as function.
     *
     * @param I $input
     * @param \Throwable $e
     * @return R
     */
    public function __invoke($input, $e)
    {
        return $this->handle($input, $e);
    }
}
