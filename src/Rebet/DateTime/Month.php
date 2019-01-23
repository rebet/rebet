<?php
namespace Rebet\DateTime;

use Rebet\Common\Path;
use Rebet\Enum\Enum;
use Rebet\Translation\FileDictionary;
use Rebet\Translation\Translator;

/**
 * Month Enum Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Month extends Enum
{
    protected const TRANSLATION_GROUP = 'datetime';

    const JANUARY  = [ 1, 'January'  , 'Jan'];
    const FEBRUARY = [ 2, 'February' , 'Feb'];
    const MARCH    = [ 3, 'March'    , 'Mar'];
    const APRIL    = [ 4, 'April'    , 'Apr'];
    const MAY      = [ 5, 'May'      , 'May'];
    const JUNE     = [ 6, 'June'     , 'Jun'];
    const JULY     = [ 7, 'July'     , 'Jul'];
    const AUGUST   = [ 8, 'August'   , 'Aug'];
    const SEPTEMBE = [ 9, 'September', 'Sep'];
    const OCTOBER  = [10, 'October'  , 'Oct'];
    const NOVEMBER = [11, 'November' , 'Nov'];
    const DECEMBER = [12, 'December' , 'Dec'];

    /**
     * @var string of short day of week label
     */
    public $label_short;

    /**
     * Create a DayOfWeek.
     *
     * @param integer $value
     * @param string $label
     * @param string $label_short
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
Translator::addResourceTo(FileDictionary::class, Path::normalize(__DIR__.'/i18n'), 'datetime');
