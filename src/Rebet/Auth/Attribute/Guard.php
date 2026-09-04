<?php
declare(strict_types=1);

namespace Rebet\Auth\Attribute;

/**
 * Guard Attribute
 *
 * USAGE:
 *  - #[Guard("web")]
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
final class Guard
{
    /**
     * @var string
     */
    public string $name;

    /**
     * Create Guard attribute.
     *
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }
}
