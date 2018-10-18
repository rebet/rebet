<?php
namespace Rebet\Validation;

use Rebet\Config\Configurable;
use Rebet\File\Files;
use Rebet\Config\Config;
use Rebet\Config\LocaleResource;

/**
 * Validator Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Validator
{
    use Configurable;
    public static function defaultConfig()
    {
        return [
            'resources_dir' => [Files::normalizePath(__DIR__.'/i18n')],
        ];
    }

    /**
     * Set current locale by given locale
     *
     * @param string $locale
     * @return void
     */
    public static function setLocale(string $locale) : void
    {
        static::setConfig(['locale' => $locale]);
    }

    /**
     * Set current fallback locale by given fallback locale
     *
     * @param string $fallback_locale
     * @return void
     */
    public static function setFallbackLocale(string $fallback_locale) : void
    {
        static::setConfig(['fallback_locale' => $fallback_locale]);
    }
}
