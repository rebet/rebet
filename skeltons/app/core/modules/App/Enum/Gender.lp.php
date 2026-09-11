<?php
declare(strict_types=1);

namespace App\Enum;

use Rebet\Tools\Enum\Enum;

/**
 * Gender Enum Class For {! $code_name !} Application
 *
 * A sample Enum that represents a person's gender, defined as [value, label] pairs.
 * You can access each Enum object like `Gender::MALE()`, and get its value/label via
 * `Gender::MALE()->value` / `Gender::MALE()->label`.
 *
 * NOTE: This is just a sample, so please freely add, remove, or rename these constants
 *       (or even this class itself) to fit your application's needs.
 *
 * @method static self MALE()
 * @method static self FEMALE()
 */
class Gender extends Enum
{
    const MALE   = [1, 'Male'];
    const FEMALE = [2, 'Female'];
}
