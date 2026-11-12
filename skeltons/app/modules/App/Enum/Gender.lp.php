<?php

declare(strict_types=1);

namespace App\Enum;

use Rebet\Tools\Enum\Enumerable;
use Rebet\Tools\Enum\Enumeration;
use Rebet\Tools\Enum\Meta;
use Rebet\Tools\Enum\Translatable;

/**
 * Gender Enum For {! $code_name !} Application
 *
 * A sample Enum that represents a person's gender, defined as a backed enum.
 * You can access each case like `Gender::Male`, and get its value/label via
 * `Gender::Male->value()` / `Gender::Male->label()`.
 *
 * The label is translated by the Translatable attribute with the translation file
 * `resources/i18n/{locale}/enum.php` (`Gender::class => ['label' => [1 => '...', 2 => '...']]`).
 * If the translation is not found then the label defined by the Meta attribute (e.g. 'Male') is used.
 *
 * NOTE: This is just a sample, so please freely add, remove, or rename these cases
 *       (or even this class itself) to fit your application's needs.
 *       If you do not need multilingual support, remove the Translatable attribute and write the Meta labels
 *       in your system language, then no translation file is needed.
 *
 * @implements Enumeration<Gender, int>
 */
#[Translatable(['label'])]
enum Gender: int implements Enumeration
{
    /** @use Enumerable<Gender, int> */
    use Enumerable;

    #[Meta(label: "Male")]
    case Male = 1;

    #[Meta(label: "Female")]
    case Female = 2;
}
