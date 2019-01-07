<?php
namespace Rebet\Config;

/**
 * Layer Class
 *
 * Class that defines the layer name of the configuration related class.
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
final class Layer
{
    /**
     * No instantiation
     */
    private function __construct()
    {
    }

    /**
     * @var string library layer
     */
    public const LIBRARY = 'library';

    /**
     * @var string framework layer
     */
    public const FRAMEWORK = 'framework';

    /**
     * @var string application layer
     */
    public const APPLICATION = 'application';

    /**
     * @var string runtime layer
     */
    public const RUNTIME = 'runtime';
}
