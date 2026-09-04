<?php
declare(strict_types=1);

namespace Rebet\Routing\Attribute;

/**
 * Alias Only Attribute
 *
 * USAGE:
 *  - #[AliasOnly]
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
final class AliasOnly
{
}
