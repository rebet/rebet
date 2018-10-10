<?php
namespace Rebet\Inflector;

use Doctrine\Inflector\CachedWordInflector;
use Doctrine\Inflector\RulesetInflector;
use Doctrine\Inflector\Rules\English;
use Rebet\Config\Configurable;
use Rebet\Common\Strings;

/**
 * Inflector Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Inflector
{
    use Configurable;
    public static function defaultConfig()
    {
        return [
            'singularizer' => new CachedWordInflector(new RulesetInflector(English\Rules::getSingularRuleset())),
            'pluralizer'   => new CachedWordInflector(new RulesetInflector(English\Rules::getPluralRuleset())),
        ];
    }

    /**
     * Converts a singular word into the format for a Rebet plural table name.
     * Converts 'ClassName' to 'class_names'.
     *
     * @param string $word
     * @param string $delimiter
     * @return string
     */
    public static function tableize(?string $word, string $replacement = '_', string $delimiters = ' _-') : ?string
    {
        return $word === null ? null : static::pluralize(static::snakize($word, $replacement, $delimiters));
    }

    /**
     * Converts a plural word into the format for a Rebet singular class name.
     * Converts 'table_names' to 'TableName'.
     *
     * @param string|null $word
     * @param string $delimiters
     * @return string|null
     */
    public static function classify(?string $word, string $delimiters = ' _-') : ?string
    {
        return $word === null ? null : static::pascalize(static::singularize($word), $delimiters);
    }

    /**
     * Converts a word into the format for a pascal case (Upper camel case) form.
     * Converts 'snake_case' to 'SnakeCase'.
     *
     * @param string|null $word
     * @param string $delimiters
     * @return string|null
     */
    public static function pascalize(?string $word, string $delimiters = ' _-') : ?string
    {
        return static::humanize($word, '', $delimiters);
    }

    /**
     * Converts a word into the format for a camel case (Lower camel case) form.
     * This uses the pascalize() method and turns the first character to lowercase.
     * Converts 'snake_case' to 'snakeCase'.
     *
     * @param string|null $word
     * @param string $delimiters
     * @return string|null
     */
    public static function camelize(?string $word, string $delimiters = ' _-') : ?string
    {
        return $word === null ? null : lcfirst(static::pascalize($word, $delimiters));
    }

    /**
     * Converts a word into the format for a snake case form.
     * Converts 'ModelName' to 'model_name'.
     *
     * @param string|null $word
     * @param string $replacement default '_'
     * @param string $delimiters default ' _-'
     * @return string|null
     */
    public static function snakize(?string $word, string $replacement = '_', string $delimiters = ' _-') : ?string
    {
        return $word === null ? null : str_replace(Strings::toCharArray($delimiters), $replacement, mb_strtolower(static::splitize($word, $replacement)));
    }

    /**
     * Converts a word into the format for a kebab case form.
     * This uses the snakize() method with '-' delimiter.
     * Converts 'ModelName' to 'model-name'.
     *
     * @param string|null $word
     * @param string $delimiters default ' _-'
     * @return string|null
     */
    public static function kebabize(?string $word, string $delimiters = ' _-') : ?string
    {
        return static::snakize($word, '-', $delimiters);
    }

    /**
     * Converts a word into the format for a human readable form.
     * Returns the input pascal/camel/snake case string like 'PascalCase/camelCase/snake_case' to human readable string like 'Pascal Case/Camel Case/Snake Case'.
     * (Delimiters are replaced by spaces and capitalized following words.)
     *
     * @param string|null $word
     * @param string $delimiters
     * @param string $replacement
     * @return string|null
     */
    public static function humanize(?string $word, string $replacement = ' ', string $delimiters = ' _-') : ?string
    {
        return $word === null ? null : str_replace(Strings::toCharArray($delimiters), $replacement, ucwords(static::splitize($word, $replacement), $delimiters));
    }

    /**
     * Converts a word into the format for a split before upper case letter form.
     *
     * @param string $word
     * @param string $delimiter
     * @return string
     */
    protected static function splitize(string $word, string $delimiter) : string
    {
        return preg_replace('~(?<=\\w)([A-Z])~u', "{$delimiter}$1", $word);
    }

    /**
     * Capitalizes all of the words by PHP's built-in ucwords function.
     *
     * @param string|null $text
     * @param string $delimiters
     * @return string|null
     */
    public static function capitalize(?string $text, string $delimiters = " \t\r\n\f\v") : ?string
    {
        return $text === null ? null : ucwords($text, $delimiters);
    }

    /**
     * Returns a word in singular form by Doctrine\Inflector\Inflector::singularize()
     *
     * @param string|null $word
     * @return string|null
     */
    public static function singularize(?string $word) : ?string
    {
        return $word === null ? null : static::config('singularizer')->inflect($word);
    }

    /**
     * Returns a word in plural form by Doctrine\Inflector\Inflector::pluralize()
     *
     * @param string|null $word
     * @return string|null
     */
    public static function pluralize(?string $word) : ?string
    {
        return $word === null ? null : static::config('pluralizer')->inflect($word);
    }
}
