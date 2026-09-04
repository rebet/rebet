<?php
declare(strict_types=1);

namespace Rebet\Routing\Attribute;

/**
 * Where Attribute
 *
 * USAGE:
 *  - #[Where(seq: "[0-9]+", code: "[a-zA-Z]+")]
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
final class Where
{
    /**
     * @var array<string, string> of where conditions, key is parameter name and value is acceptable regex.
     */
    public array $wheres = [];

    /**
     * Create Where attribute.
     *
     * @param string ...$wheres of where conditions, key is parameter name and value is acceptable regex.
     */
    public function __construct(string ...$wheres)
    {
        $this->wheres = $wheres;
    }
}
