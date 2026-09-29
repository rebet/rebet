<?php

declare(strict_types=1);

namespace Rebet\Tools\DateTime;

use Rebet\Tools\Enum\Enum;
use Rebet\Tools\Translation\FileDictionary;
use Rebet\Tools\Translation\Translator;
use Rebet\Tools\Utility\Path;

/**
 * Month Enum Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 *
 * @method static self JANUARY()
 * @method static self FEBRUARY()
 * @method static self MARCH()
 * @method static self APRIL()
 * @method static self MAY()
 * @method static self JUNE()
 * @method static self JULY()
 * @method static self AUGUST()
 * @method static self SEPTEMBER()
 * @method static self OCTOBER()
 * @method static self NOVEMBER()
 * @method static self DECEMBER()
 */
class Month extends Enum
{
    protected const TRANSLATION_GROUP = 'datetime';

    public const JANUARY  = [ 1, 'January', 'Jan'];
    public const FEBRUARY = [ 2, 'February', 'Feb'];
    public const MARCH    = [ 3, 'March', 'Mar'];
    public const APRIL    = [ 4, 'April', 'Apr'];
    public const MAY      = [ 5, 'May', 'May'];
    public const JUNE     = [ 6, 'June', 'Jun'];
    public const JULY     = [ 7, 'July', 'Jul'];
    public const AUGUST   = [ 8, 'August', 'Aug'];
    public const SEPTEMBE = [ 9, 'September', 'Sep'];
    public const OCTOBER  = [10, 'October', 'Oct'];
    public const NOVEMBER = [11, 'November', 'Nov'];
    public const DECEMBER = [12, 'December', 'Dec'];

    /**
     * @var string of short day of week label
     */
    public $label_short;

    /**
     * Create a DayOfWeek.
     *
     * @param integer $value
     * @param string  $label
     * @param string  $label_short
     */
    protected function __construct(int $value, string $label, string $label_short)
    {
        parent::__construct($value, $label);
        $this->label_short = $label_short;
    }
}

// ---------------------------------------------------------
// Add library default translation resource
// ---------------------------------------------------------
Translator::addResourceTo(FileDictionary::class, Path::normalize(__DIR__ . '/i18n'), 'datetime');
