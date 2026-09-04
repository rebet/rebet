<?php
declare(strict_types=1);

namespace Rebet\Database\Attribute;

/**
 * Primary Key Attribute
 *
 * Specifies the entity property that should be ignored insert/update query by auto build.
 *
 * USAGE:
 *  - #[PrimaryKey]
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class PrimaryKey
{
}
