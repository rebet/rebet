<?php
namespace Rebet\Routing\Attribute;

/**
 * Not Routing Attribute
 *
 * USAGE:
 *  - #[NotRouting]
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
final class NotRouting
{
}
