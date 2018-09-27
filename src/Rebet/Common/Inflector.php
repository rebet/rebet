<?php
namespace Rebet\Common;

use Rebet\Config\Configurable;

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         0.2.9
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Inflector Class
 *
 * This class based on Cake\Utility\Inflector of cakephp/cakephp ver 3.6.11.
 *
 * Function diffs between CakePHP and Rebet are like below;
 *  - remove: slug() deprecated function on CakePHP (deprecated CakePHP 3.2.7)
 *  + supported: Rebet\Config\Configurable for rules
 *
 * @see https://github.com/cakephp/cakephp/blob/3.6.11/src/Utility/Inflector.php
 * @see https://github.com/cakephp/cakephp/blob/3.6.11/LICENSE
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
            // Plural inflector rules
            'plural' => [
                ['/(s)tatus$/i', '\1tatuses'],
                ['/(quiz)$/i', '\1zes'],
                ['/^(ox)$/i', '\1\2en'],
                ['/([m|l])ouse$/i', '\1ice'],
                ['/(matr|vert|ind)(ix|ex)$/i', '\1ices'],
                ['/(x|ch|ss|sh)$/i', '\1es'],
                ['/([^aeiouy]|qu)y$/i', '\1ies'],
                ['/(hive)$/i', '\1s'],
                ['/(chef)$/i', '\1s'],
                ['/(?:([^f])fe|([lre])f)$/i', '\1\2ves'],
                ['/sis$/i', 'ses'],
                ['/([ti])um$/i', '\1a'],
                ['/(p)erson$/i', '\1eople'],
                ['/(?<!u)(m)an$/i', '\1en'],
                ['/(c)hild$/i', '\1hildren'],
                ['/(buffal|tomat)o$/i', '\1\2oes'],
                ['/(alumn|bacill|cact|foc|fung|nucle|radi|stimul|syllab|termin)us$/i', '\1i'],
                ['/us$/i', 'uses'],
                ['/(alias)$/i', '\1es'],
                ['/(ax|cris|test)is$/i', '\1es'],
                ['/s$/', 's'],
                ['/^$/', ''],
                ['/$/', 's'],
            ],
            
            // Singular inflector rules
            'singular' => [
                ['/(s)tatuses$/i', '\1\2tatus'],
                ['/^(.*)(menu)s$/i', '\1\2'],
                ['/(quiz)zes$/i', '\\1'],
                ['/(matr)ices$/i', '\1ix'],
                ['/(vert|ind)ices$/i', '\1ex'],
                ['/^(ox)en/i', '\1'],
                ['/(alias)(es)*$/i', '\1'],
                ['/(alumn|bacill|cact|foc|fung|nucle|radi|stimul|syllab|termin|viri?)i$/i', '\1us'],
                ['/([ftw]ax)es/i', '\1'],
                ['/(cris|ax|test)es$/i', '\1is'],
                ['/(shoe)s$/i', '\1'],
                ['/(o)es$/i', '\1'],
                ['/ouses$/', 'ouse'],
                ['/([^a])uses$/', '\1us'],
                ['/([m|l])ice$/i', '\1ouse'],
                ['/(x|ch|ss|sh)es$/i', '\1'],
                ['/(m)ovies$/i', '\1\2ovie'],
                ['/(s)eries$/i', '\1\2eries'],
                ['/([^aeiouy]|qu)ies$/i', '\1y'],
                ['/(tive)s$/i', '\1'],
                ['/(hive)s$/i', '\1'],
                ['/(drive)s$/i', '\1'],
                ['/([le])ves$/i', '\1f'],
                ['/([^rfoa])ves$/i', '\1fe'],
                ['/(^analy)ses$/i', '\1sis'],
                ['/(analy|diagno|^ba|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i', '\1\2sis'],
                ['/([ti])a$/i', '\1um'],
                ['/(p)eople$/i', '\1\2erson'],
                ['/(m)en$/i', '\1an'],
                ['/(c)hildren$/i', '\1\2hild'],
                ['/(n)ews$/i', '\1\2ews'],
                ['/eaus$/', 'eau'],
                ['/^(.*us)$/', '\\1'],
                ['/s$/i', ''],
            ],
            
            // Irregular rules
            'irregular' => [
                'atlas' => 'atlases',
                'beef' => 'beefs',
                'brief' => 'briefs',
                'brother' => 'brothers',
                'cafe' => 'cafes',
                'child' => 'children',
                'cookie' => 'cookies',
                'corpus' => 'corpuses',
                'cow' => 'cows',
                'criterion' => 'criteria',
                'ganglion' => 'ganglions',
                'genie' => 'genies',
                'genus' => 'genera',
                'graffito' => 'graffiti',
                'hoof' => 'hoofs',
                'loaf' => 'loaves',
                'man' => 'men',
                'money' => 'monies',
                'mongoose' => 'mongooses',
                'move' => 'moves',
                'mythos' => 'mythoi',
                'niche' => 'niches',
                'numen' => 'numina',
                'occiput' => 'occiputs',
                'octopus' => 'octopuses',
                'opus' => 'opuses',
                'ox' => 'oxen',
                'penis' => 'penises',
                'person' => 'people',
                'sex' => 'sexes',
                'soliloquy' => 'soliloquies',
                'testis' => 'testes',
                'trilby' => 'trilbys',
                'turf' => 'turfs',
                'potato' => 'potatoes',
                'hero' => 'heroes',
                'tooth' => 'teeth',
                'goose' => 'geese',
                'foot' => 'feet',
                'foe' => 'foes',
                'sieve' => 'sieves',
                'cache' => 'caches',
            ],
            
            // Words that should not be inflected
            'uninflected' => [
                '.*[nrlm]ese', '.*data', '.*deer', '.*fish', '.*measles', '.*ois',
                '.*pox', '.*sheep', 'people', 'feedback', 'stadia', '.*?media',
                'chassis', 'clippers', 'debris', 'diabetes', 'equipment', 'gallows',
                'graffiti', 'headquarters', 'information', 'innings', 'news', 'nexus',
                'pokemon', 'proceedings', 'research', 'sea[- ]bass', 'series', 'species', 'weather'
            ],
        ];
    }

    /**
     * Method cache array.
     *
     * @var array
     */
    protected static $_cache = [];

    /**
     * The initial state of Inflector so reset() works.
     *
     * @var array
     */
    protected static $_initialState = [];

    /**
     * Cache inflected values, and return if already available
     *
     * @param string $type Inflection type
     * @param string $key Original value
     * @param string|bool $value Inflected value
     * @return string|false Inflected value on cache hit or false on cache miss.
     */
    protected static function _cache($type, $key, $value = false)
    {
        $key = '_' . $key;
        $type = '_' . $type;
        if ($value !== false) {
            static::$_cache[$type][$key] = $value;

            return $value;
        }
        if (!isset(static::$_cache[$type][$key])) {
            return false;
        }

        return static::$_cache[$type][$key];
    }

    /**
     * Clears Inflectors inflected value caches. And resets the inflection
     * rules to the initial values.
     *
     * @return void
     */
    public static function reset()
    {
        if (empty(static::$_initialState)) {
            static::$_initialState = get_class_vars(__CLASS__);

            return;
        }
        foreach (static::$_initialState as $key => $val) {
            if ($key !== '_initialState') {
                static::${$key} = $val;
            }
        }
    }

    /**
     * Adds custom inflection $rules, of either 'plural', 'singular',
     * 'uninflected' or 'irregular' $type.
     *
     * ### Usage:
     *
     * ```
     * Inflector::rules('plural', [['/^(inflect)or$/i', '\1ables']]);
     * Inflector::rules('irregular', ['red' => 'redlings']);
     * Inflector::rules('uninflected', ['dontinflectme']);
     * ```
     *
     * @param string $type The type of inflection, either 'plural', 'singular' or 'uninflected'.
     * @param array $rules Array of rules to be added.
     * @return void
     */
    public static function rules($type, $rules)
    {
        static::setConfig([$type => $rules]);
        static::$_cache = [];
    }

    /**
     * Return $word in plural form.
     *
     * @param string $word Word in singular
     * @return string Word in plural
     * @link https://book.cakephp.org/3.0/en/core-libraries/inflector.html#creating-plural-singular-forms
     */
    public static function pluralize($word)
    {
        if (isset(static::$_cache['pluralize'][$word])) {
            return static::$_cache['pluralize'][$word];
        }

        if (!isset(static::$_cache['irregular']['pluralize'])) {
            static::$_cache['irregular']['pluralize'] = '(?:' . implode('|', array_keys(static::config('irregular'))) . ')';
        }

        if (preg_match('/(.*?(?:\\b|_))(' . static::$_cache['irregular']['pluralize'] . ')$/i', $word, $regs)) {
            static::$_cache['pluralize'][$word] = $regs[1] . substr($regs[2], 0, 1) .
                substr((static::config('irregular'))[strtolower($regs[2])], 1);

            return static::$_cache['pluralize'][$word];
        }

        if (!isset(static::$_cache['uninflected'])) {
            static::$_cache['uninflected'] = '(?:' . implode('|', static::config('uninflected')) . ')';
        }

        if (preg_match('/^(' . static::$_cache['uninflected'] . ')$/i', $word, $regs)) {
            static::$_cache['pluralize'][$word] = $word;

            return $word;
        }

        foreach (static::config('plural') as [$rule, $replacement]) {
            if (preg_match($rule, $word)) {
                static::$_cache['pluralize'][$word] = preg_replace($rule, $replacement, $word);

                return static::$_cache['pluralize'][$word];
            }
        }
    }

    /**
     * Return $word in singular form.
     *
     * @param string $word Word in plural
     * @return string Word in singular
     * @link https://book.cakephp.org/3.0/en/core-libraries/inflector.html#creating-plural-singular-forms
     */
    public static function singularize($word)
    {
        if (isset(static::$_cache['singularize'][$word])) {
            return static::$_cache['singularize'][$word];
        }

        if (!isset(static::$_cache['irregular']['singular'])) {
            static::$_cache['irregular']['singular'] = '(?:' . implode('|', static::config('irregular')) . ')';
        }

        if (preg_match('/(.*?(?:\\b|_))(' . static::$_cache['irregular']['singular'] . ')$/i', $word, $regs)) {
            static::$_cache['singularize'][$word] = $regs[1] . substr($regs[2], 0, 1) .
                substr(array_search(strtolower($regs[2]), static::config('irregular')), 1);

            return static::$_cache['singularize'][$word];
        }

        if (!isset(static::$_cache['uninflected'])) {
            static::$_cache['uninflected'] = '(?:' . implode('|', static::config('uninflected')) . ')';
        }

        if (preg_match('/^(' . static::$_cache['uninflected'] . ')$/i', $word, $regs)) {
            static::$_cache['pluralize'][$word] = $word;

            return $word;
        }

        foreach (static::config('singular', false, []) as [$rule, $replacement]) {
            if (preg_match($rule, $word)) {
                static::$_cache['singularize'][$word] = preg_replace($rule, $replacement, $word);

                return static::$_cache['singularize'][$word];
            }
        }
        static::$_cache['singularize'][$word] = $word;

        return $word;
    }

    /**
     * Returns the input lower_case_delimited_string as a CamelCasedString.
     *
     * @param string $string String to camelize
     * @param string $delimiter the delimiter in the input string
     * @return string CamelizedStringLikeThis.
     * @link https://book.cakephp.org/3.0/en/core-libraries/inflector.html#creating-camelcase-and-under-scored-forms
     */
    public static function camelize($string, $delimiter = '_')
    {
        $cacheKey = __FUNCTION__ . $delimiter;

        $result = static::_cache($cacheKey, $string);

        if ($result === false) {
            $result = str_replace(' ', '', static::humanize($string, $delimiter));
            static::_cache($cacheKey, $string, $result);
        }

        return $result;
    }

    /**
     * Returns the input CamelCasedString as an underscored_string.
     *
     * Also replaces dashes with underscores
     *
     * @param string $string CamelCasedString to be "underscorized"
     * @return string underscore_version of the input string
     * @link https://book.cakephp.org/3.0/en/core-libraries/inflector.html#creating-camelcase-and-under-scored-forms
     */
    public static function underscore($string)
    {
        return static::delimit(str_replace('-', '_', $string), '_');
    }

    /**
     * Returns the input CamelCasedString as an dashed-string.
     *
     * Also replaces underscores with dashes
     *
     * @param string $string The string to dasherize.
     * @return string Dashed version of the input string
     */
    public static function dasherize($string)
    {
        return static::delimit(str_replace('_', '-', $string), '-');
    }

    /**
     * Returns the input lower_case_delimited_string as 'A Human Readable String'.
     * (Underscores are replaced by spaces and capitalized following words.)
     *
     * @param string $string String to be humanized
     * @param string $delimiter the character to replace with a space
     * @return string Human-readable string
     * @link https://book.cakephp.org/3.0/en/core-libraries/inflector.html#creating-human-readable-forms
     */
    public static function humanize($string, $delimiter = '_')
    {
        $cacheKey = __FUNCTION__ . $delimiter;

        $result = static::_cache($cacheKey, $string);

        if ($result === false) {
            $result = explode(' ', str_replace($delimiter, ' ', $string));
            foreach ($result as &$word) {
                $word = mb_strtoupper(mb_substr($word, 0, 1)) . mb_substr($word, 1);
            }
            $result = implode(' ', $result);
            static::_cache($cacheKey, $string, $result);
        }

        return $result;
    }

    /**
     * Expects a CamelCasedInputString, and produces a lower_case_delimited_string
     *
     * @param string $string String to delimit
     * @param string $delimiter the character to use as a delimiter
     * @return string delimited string
     */
    public static function delimit($string, $delimiter = '_')
    {
        $cacheKey = __FUNCTION__ . $delimiter;

        $result = static::_cache($cacheKey, $string);

        if ($result === false) {
            $result = mb_strtolower(preg_replace('/(?<=\\w)([A-Z])/', $delimiter . '\\1', $string));
            static::_cache($cacheKey, $string, $result);
        }

        return $result;
    }

    /**
     * Returns corresponding table name for given model $className. ("people" for the model class "Person").
     *
     * @param string $className Name of class to get database table name for
     * @return string Name of the database table for given class
     * @link https://book.cakephp.org/3.0/en/core-libraries/inflector.html#creating-table-and-class-name-forms
     */
    public static function tableize($className)
    {
        $result = static::_cache(__FUNCTION__, $className);

        if ($result === false) {
            $result = static::pluralize(static::underscore($className));
            static::_cache(__FUNCTION__, $className, $result);
        }

        return $result;
    }

    /**
     * Returns Cake model class name ("Person" for the database table "people".) for given database table.
     *
     * @param string $tableName Name of database table to get class name for
     * @return string Class name
     * @link https://book.cakephp.org/3.0/en/core-libraries/inflector.html#creating-table-and-class-name-forms
     */
    public static function classify($tableName)
    {
        $result = static::_cache(__FUNCTION__, $tableName);

        if ($result === false) {
            $result = static::camelize(static::singularize($tableName));
            static::_cache(__FUNCTION__, $tableName, $result);
        }

        return $result;
    }

    /**
     * Returns camelBacked version of an underscored string.
     *
     * @param string $string String to convert.
     * @return string in variable form
     * @link https://book.cakephp.org/3.0/en/core-libraries/inflector.html#creating-variable-names
     */
    public static function variable($string)
    {
        $result = static::_cache(__FUNCTION__, $string);

        if ($result === false) {
            $camelized = static::camelize(static::underscore($string));
            $replace = strtolower(substr($camelized, 0, 1));
            $result = $replace . substr($camelized, 1);
            static::_cache(__FUNCTION__, $string, $result);
        }

        return $result;
    }
}
