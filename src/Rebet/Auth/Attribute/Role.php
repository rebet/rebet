<?php
namespace Rebet\Auth\Attribute;

/**
 * Role Attribute
 *
 * USAGE:
 *  - #[Role("all")]
 *  - #[Role("user")]
 *  - #[Role("user", "admin")]
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
final class Role
{
    /**
     * @var array<string> of acceptable role names
     */
    public array $names = [];

    /**
     * Create Role attribute.
     *
     * @param string ...$names of acceptable role names
     */
    public function __construct(string ...$names)
    {
        $this->names = $names;
    }
}
