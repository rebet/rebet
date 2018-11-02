<?php
namespace Rebet\Validation;

use Rebet\Enum\Enum;

/**
 * Validation Check Kind Enum Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Kind extends Enum
{
    const TYPE_CONSISTENCY_CHECK = [1, 'TYPE_CONSISTENCY_CHECK'];
    const TYPE_DEPENDENT_CHECK   = [2, 'TYPE_DEPENDENT_CHECK'];
    const OTHER                  = [3, 'OTHER'];
}
