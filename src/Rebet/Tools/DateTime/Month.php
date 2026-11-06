<?php

declare(strict_types=1);

namespace Rebet\Tools\DateTime;

use Rebet\Tools\Enum\Enumerable;
use Rebet\Tools\Enum\Enumeration;
use Rebet\Tools\Enum\Meta;
use Rebet\Tools\Translation\FileDictionary;
use Rebet\Tools\Translation\Translator;
use Rebet\Tools\Utility\Path;

/**
 * Month Enum
 *
 * The value is the same as the 'n' format of date() (January is 1 and December is 12).
 * The labels of the metadata are the English names, and they are translated by the 'datetime' translation group.
 *
 * - label       : The full name (e.g. January)
 * - label_short : The short name (e.g. Jan)
 *
 * The metadata (label_short) can be read by the method call (not translated),
 * and translate() returns the translated one. (e.g. translate('label_short'))
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 *
 * @method string label_short() The short name (e.g. Jan), which is not translated.
 *
 * @implements Enumeration<Month, int>
 */
enum Month: int implements Enumeration
{
    /** @use Enumerable<Month, int> */
    use Enumerable;

    #[Meta(label: 'January', label_short: 'Jan')]
    case January = 1;

    #[Meta(label: 'February', label_short: 'Feb')]
    case February = 2;

    #[Meta(label: 'March', label_short: 'Mar')]
    case March = 3;

    #[Meta(label: 'April', label_short: 'Apr')]
    case April = 4;

    #[Meta(label: 'May', label_short: 'May')]
    case May = 5;

    #[Meta(label: 'June', label_short: 'Jun')]
    case June = 6;

    #[Meta(label: 'July', label_short: 'Jul')]
    case July = 7;

    #[Meta(label: 'August', label_short: 'Aug')]
    case August = 8;

    #[Meta(label: 'September', label_short: 'Sep')]
    case September = 9;

    #[Meta(label: 'October', label_short: 'Oct')]
    case October = 10;

    #[Meta(label: 'November', label_short: 'Nov')]
    case November = 11;

    #[Meta(label: 'December', label_short: 'Dec')]
    case December = 12;

    /**
     * The translation group of Month.
     *
     * @return string
     */
    protected static function translationGroup(): string
    {
        return 'datetime';
    }
}

// ---------------------------------------------------------
// Add library default translation resource
// ---------------------------------------------------------
Translator::addResourceTo(FileDictionary::class, Path::normalize(__DIR__ . '/i18n'), 'datetime');
