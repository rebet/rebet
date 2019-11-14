<?php
namespace Rebet\Inflection;

use Rebet\Common\Arrays;
use Rebet\Common\Exception\LogicException;
use Rebet\Common\Strings;
use Rebet\Common\Utils;
use Rebet\Config\Configurable;

/**
 * Inflector Class
 *
 * Fonction pluralize and singularize implementation are borrowed from doctrine/inflector ver 1.3.x with some modifications.
 * And the Inflector of Rebet dosen't have rules() interface because of the rules become Configurable.
 * So if you want to custamaize a rule, you can use Config settings like below;
 *
 * Config::application([
 *     Inflector::class => [
 *         'plural' => [
 *             'rules' => [
 *                 ['/new regex/', 'new replacement'],
 *             ],
 *             'uninflected' => [
 *                  'new uninflected',
 *             ]
 *             'irregular' => [
 *                  'new irregular' => 'new replacement',
 *             ]
 *         ],
 *         'singular' => [
 *             'rules' => [
 *                 ['/new regex/', 'new replacement'],
 *             ],
 *             'uninflected' => [
 *                  'new uninflected',
 *             ]
 *             'irregular' => [
 *                  'new irregular' => 'new replacement',
 *             ]
 *         ],
 *         'uninflected' => [
 *             'new uninflected',
 *         ]
 *     ]
 * ]);
 *
 * @see https://github.com/doctrine/inflector/blob/1.3.x/lib/Doctrine/Common/Inflector/Inflector.php
 * @see https://github.com/doctrine/inflector/blob/1.3.x/LICENSE
 * @see Rebet\Config\Config
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
            'plural' => [
                'rules' => [
                    ['/(s)tatus$/i', '\1\2tatuses'],
                    ['/(quiz)$/i', '\1zes'],
                    ['/^(ox)$/i', '\1\2en'],
                    ['/([m|l])ouse$/i', '\1ice'],
                    ['/(matr|vert|ind)(ix|ex)$/i', '\1ices'],
                    ['/(x|ch|ss|sh)$/i', '\1es'],
                    ['/([^aeiouy]|qu)y$/i', '\1ies'],
                    ['/(hive|gulf)$/i', '\1s'],
                    ['/(?:([^f])fe|([lr])f)$/i', '\1\2ves'],
                    ['/sis$/i', 'ses'],
                    ['/([ti])um$/i', '\1a'],
                    ['/(c)riterion$/i', '\1riteria'],
                    ['/(p)erson$/i', '\1eople'],
                    ['/(m)an$/i', '\1en'],
                    ['/(c)hild$/i', '\1hildren'],
                    ['/(f)oot$/i', '\1eet'],
                    ['/(buffal|her|potat|tomat|volcan)o$/i', '\1\2oes'],
                    ['/(alumn|bacill|cact|foc|fung|nucle|radi|stimul|syllab|termin|vir)us$/i', '\1i'],
                    ['/us$/i', 'uses'],
                    ['/(alias)$/i', '\1es'],
                    ['/(analys|ax|cris|test|thes)is$/i', '\1es'],
                    ['/s$/', 's'],
                    ['/^$/', ''],
                    ['/$/', 's'],
                ],
                'uninflected' => [
                    '.*[nrlm]ese',
                    '.*deer',
                    '.*fish',
                    '.*measles',
                    '.*ois',
                    '.*pox',
                    '.*sheep',
                    'people',
                    'cookie',
                    'police',
                ],
                'irregular' => [
                    'atlas'        => 'atlases',
                    'axe'          => 'axes',
                    'beef'         => 'beefs',
                    'brother'      => 'brothers',
                    'cafe'         => 'cafes',
                    'chateau'      => 'chateaux',
                    'niveau'       => 'niveaux',
                    'child'        => 'children',
                    'cookie'       => 'cookies',
                    'corpus'       => 'corpuses',
                    'cow'          => 'cows',
                    'criterion'    => 'criteria',
                    'curriculum'   => 'curricula',
                    'demo'         => 'demos',
                    'domino'       => 'dominoes',
                    'echo'         => 'echoes',
                    'foot'         => 'feet',
                    'fungus'       => 'fungi',
                    'ganglion'     => 'ganglions',
                    'genie'        => 'genies',
                    'genus'        => 'genera',
                    'goose'        => 'geese',
                    'graffito'     => 'graffiti',
                    'hippopotamus' => 'hippopotami',
                    'hoof'         => 'hoofs',
                    'human'        => 'humans',
                    'iris'         => 'irises',
                    'larva'        => 'larvae',
                    'leaf'         => 'leaves',
                    'loaf'         => 'loaves',
                    'man'          => 'men',
                    'medium'       => 'media',
                    'memorandum'   => 'memoranda',
                    'money'        => 'monies',
                    'mongoose'     => 'mongooses',
                    'motto'        => 'mottoes',
                    'move'         => 'moves',
                    'mythos'       => 'mythoi',
                    'niche'        => 'niches',
                    'nucleus'      => 'nuclei',
                    'numen'        => 'numina',
                    'occiput'      => 'occiputs',
                    'octopus'      => 'octopuses',
                    'opus'         => 'opuses',
                    'ox'           => 'oxen',
                    'passerby'     => 'passersby',
                    'penis'        => 'penises',
                    'person'       => 'people',
                    'plateau'      => 'plateaux',
                    'runner-up'    => 'runners-up',
                    'sex'          => 'sexes',
                    'soliloquy'    => 'soliloquies',
                    'son-in-law'   => 'sons-in-law',
                    'syllabus'     => 'syllabi',
                    'testis'       => 'testes',
                    'thief'        => 'thieves',
                    'tooth'        => 'teeth',
                    'tornado'      => 'tornadoes',
                    'trilby'       => 'trilbys',
                    'turf'         => 'turfs',
                    'valve'        => 'valves',
                    'volcano'      => 'volcanoes',
                ],
            ],

            'singular' => [
                'rules' => [
                    ['/(s)tatuses$/i', '\1\2tatus'],
                    ['/^(.*)(menu)s$/i', '\1\2'],
                    ['/(quiz)zes$/i', '\\1'],
                    ['/(matr)ices$/i', '\1ix'],
                    ['/(vert|ind)ices$/i', '\1ex'],
                    ['/^(ox)en/i', '\1'],
                    ['/(alias)(es)*$/i', '\1'],
                    ['/(buffal|her|potat|tomat|volcan)oes$/i', '\1o'],
                    ['/(alumn|bacill|cact|foc|fung|nucle|radi|stimul|syllab|termin|viri?)i$/i', '\1us'],
                    ['/([ftw]ax)es/i', '\1'],
                    ['/(analys|ax|cris|test|thes)es$/i', '\1is'],
                    ['/(shoe|slave)s$/i', '\1'],
                    ['/(o)es$/i', '\1'],
                    ['/ouses$/', 'ouse'],
                    ['/([^a])uses$/', '\1us'],
                    ['/([m|l])ice$/i', '\1ouse'],
                    ['/(x|ch|ss|sh)es$/i', '\1'],
                    ['/(m)ovies$/i', '\1\2ovie'],
                    ['/(s)eries$/i', '\1\2eries'],
                    ['/([^aeiouy]|qu)ies$/i', '\1y'],
                    ['/([lr])ves$/i', '\1f'],
                    ['/(tive)s$/i', '\1'],
                    ['/(hive)s$/i', '\1'],
                    ['/(drive)s$/i', '\1'],
                    ['/(dive)s$/i', '\1'],
                    ['/(olive)s$/i', '\1'],
                    ['/([^fo])ves$/i', '\1fe'],
                    ['/(^analy)ses$/i', '\1sis'],
                    ['/(analy|diagno|^ba|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i', '\1\2sis'],
                    ['/(c)riteria$/i', '\1riterion'],
                    ['/([ti])a$/i', '\1um'],
                    ['/(p)eople$/i', '\1\2erson'],
                    ['/(m)en$/i', '\1an'],
                    ['/(c)hildren$/i', '\1\2hild'],
                    ['/(f)eet$/i', '\1oot'],
                    ['/(n)ews$/i', '\1\2ews'],
                    ['/eaus$/', 'eau'],
                    ['/^(.*us)$/', '\\1'],
                    ['/s$/i', ''],
                ],
                'uninflected' => [
                    '.*[nrlm]ese',
                    '.*deer',
                    '.*fish',
                    '.*measles',
                    '.*ois',
                    '.*pox',
                    '.*sheep',
                    '.*ss',
                    'data',
                    'police',
                    'pants',
                    'clothes',
                ],
                'irregular' => [
                    'abuses'     => 'abuse',
                    'avalanches' => 'avalanche',
                    'caches'     => 'cache',
                    'criteria'   => 'criterion',
                    'curves'     => 'curve',
                    'emphases'   => 'emphasis',
                    'foes'       => 'foe',
                    'geese'      => 'goose',
                    'graves'     => 'grave',
                    'hoaxes'     => 'hoax',
                    'media'      => 'medium',
                    'neuroses'   => 'neurosis',
                    'waves'      => 'wave',
                    'oases'      => 'oasis',
                    'valves'     => 'valve',
                ],
            ],

            'uninflected' => [
                '.*?media', 'Amoyese', 'audio', 'bison', 'Borghese', 'bream', 'breeches',
                'britches', 'buffalo', 'cantus', 'carp', 'chassis', 'clippers', 'cod', 'coitus', 'compensation', 'Congoese',
                'contretemps', 'coreopsis', 'corps', 'data', 'debris', 'deer', 'diabetes', 'djinn', 'education', 'eland',
                'elk', 'emoji', 'equipment', 'evidence', 'Faroese', 'feedback', 'fish', 'flounder', 'Foochowese',
                'Furniture', 'furniture', 'gallows', 'Genevese', 'Genoese', 'Gilbertese', 'gold',
                'headquarters', 'herpes', 'hijinks', 'Hottentotese', 'information', 'innings', 'jackanapes', 'jedi',
                'Kiplingese', 'knowledge', 'Kongoese', 'love', 'Lucchese', 'Luggage', 'mackerel', 'Maltese', 'metadata',
                'mews', 'moose', 'mumps', 'Nankingese', 'news', 'nexus', 'Niasese', 'nutrition', 'offspring',
                'Pekingese', 'Piedmontese', 'pincers', 'Pistoiese', 'plankton', 'pliers', 'pokemon', 'police', 'Portuguese',
                'proceedings', 'rabies', 'rain', 'rhinoceros', 'rice', 'salmon', 'Sarawakese', 'scissors', 'sea[- ]bass',
                'series', 'Shavese', 'shears', 'sheep', 'siemens', 'species', 'staff', 'swine', 'traffic',
                'trousers', 'trout', 'tuna', 'us', 'Vermontese', 'Wenchowese', 'wheat', 'whiting', 'wildebeest', 'Yengeese',
            ]
        ];
    }

    /**
     * pluralize/singularize cache array.
     *
     * @var array
     */
    private static $cache = [
        'pluralize'   => [],
        'singularize' => [],
    ];

    /**
     * pluralize/singularize compiled array.
     *
     * @var array
     */
    private static $compiled = [
        'pluralize'   => [],
        'singularize' => [],
    ];

    /**
     * Clears Inflectors inflected value caches, and resets the inflection
     * rules to the initial values.
     */
    public static function reset() : void
    {
        static::clearConfig();
        static::$cache    = [
            'pluralize'   => [],
            'singularize' => [],
        ];
        static::$compiled = [
            'pluralize'   => [],
            'singularize' => [],
        ];
    }

    /**
     * Converts a singular word into the format for a Rebet plural table name.
     * Converts 'ClassName' to 'class_names'.
     *
     * @param string $word
     * @param string $replacement (default: '_')
     * @param string $delimiters (default: ' _-')
     * @return string|null
     */
    public static function tableize(?string $word, string $replacement = '_', string $delimiters = ' _-') : ?string
    {
        return $word === null ? null : static::pluralize(static::snakize($word, $replacement, $delimiters));
    }

    /**
     * Converts a singular word or two plural words into the format for a Rebet pivot (many to many relation) table name.
     * Pivot table name will be joined name of two singular table names order by natural.
     * Converts 'ClassName' to 'class_name' and ['tables', 'others'] to 'other_table'.
     * NOTE: When the array words given then the words count must be two otherwise return null.
     *
     * @param string|array|null $words
     * @param string $replacement (default: '_')
     * @param string $delimiters (default: ' _-')
     * @return string|null
     */
    public static function pivotize($word, string $replacement = '_', string $delimiters = ' _-') : ?string
    {
        if ($word === null) {
            return null;
        }

        if (!is_array($word)) {
            return static::singularize(static::snakize($word, $replacement, $delimiters));
        }

        $word = Arrays::compact($word);
        if (count($word) !== 2) {
            return null;
        }
        return implode('_', array_sort(array_map(function ($value) use ($replacement, $delimiters) {
            return static::singularize(static::snakize($value, $replacement, $delimiters));
        }, $word)));
    }

    /**
     * Converts a word into the format for a Rebet singular primary key name.
     * Converts 'ClassName' to 'class_name_id' and 'table_names' to 'table_name_id'.
     *
     * @param string $word
     * @param string $replacement (default: '_')
     * @param string $delimiters (default: ' _-')
     * @return string|null
     */
    public static function primarize(?string $word, string $replacement = '_', string $delimiters = ' _-') : ?string
    {
        return Utils::isBlank($word) ? $word : static::singularize(static::snakize($word, $replacement, $delimiters)).'_id';
    }

    /**
     * Converts a plural word into the format for a Rebet singular class name.
     * Converts 'table_names' to 'TableName'.
     *
     * @param string|null $word
     * @param string $delimiters (default: ' _-')
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
     * @param string $delimiters (default: ' _-')
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
     * @param string $delimiters (default: ' _-')
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
     * @param string $replacement (default: '_')
     * @param string $delimiters (default: ' _-')
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
     * @param string $delimiters (default: ' _-')
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
     * @param string $replacement (default: ' ')
     * @param string $delimiters (default: ' _-')
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
     * @param string $delimiters (default: ' \t\r\n\f\v')
     * @return string|null
     */
    public static function capitalize(?string $text, string $delimiters = " \t\r\n\f\v") : ?string
    {
        return $text === null ? null : ucwords($text, $delimiters);
    }

    /**
     * Returns a word in plural form.
     *
     * @param string|null $word
     * @return string|null
     */
    public static function pluralize(?string $word) : ?string
    {
        $cache    = static::$cache['pluralize'];
        $compiled = static::$compiled['pluralize'];

        if ($word === null) {
            return null;
        }
        if (isset($cache[$word])) {
            return $cache[$word];
        }
        if (!isset($compiled['merged']['irregular'])) {
            $compiled['merged']['irregular'] = static::config('plural.irregular', false, []);
        }
        if (!isset($compiled['merged']['uninflected'])) {
            $compiled['merged']['uninflected'] = array_merge(static::config('plural.uninflected', false, []), static::config('uninflected', false, []));
        }
        if (!isset($compiled['regex']['uninflected']) || !isset($compiled['regex']['irregular'])) {
            $compiled['regex']['uninflected'] = '(?:' . implode('|', $compiled['merged']['uninflected']) . ')';
            $compiled['regex']['irregular']   = '(?:' . implode('|', array_keys($compiled['merged']['irregular'])) . ')';
        }
        if (preg_match('/(.*)\\b(' . $compiled['regex']['irregular'] . ')$/i', $word, $regs)) {
            $cache[$word] = $regs[1] . $word[0] . substr($compiled['merged']['irregular'][strtolower($regs[2])], 1);
            return $cache[$word];
        }
        if (preg_match('/^(' . $compiled['regex']['uninflected'] . ')$/i', $word, $regs)) {
            $cache[$word] = $word;
            return $word;
        }
        foreach (static::config('plural.rules', false, []) as [$rule, $replacement]) {
            if (preg_match($rule, $word)) {
                $cache[$word] = preg_replace($rule, $replacement, $word);
                return $cache[$word];
            }
        }

        throw LogicException::by("Can't convert the word '{$word}' to plural from. Please review the plural.rules config.");
    }

    /**
     * Returns a word in singular form.
     *
     * @param string|null $word
     * @return string|null
     */
    public static function singularize(?string $word) : ?string
    {
        $cache    = static::$cache['singularize'];
        $compiled = static::$compiled['singularize'];

        if ($word === null) {
            return null;
        }
        if (isset($cache[$word])) {
            return $cache[$word];
        }
        if (!isset($compiled['merged']['irregular'])) {
            $compiled['merged']['irregular'] = array_merge(static::config('singular.irregular', false, []), array_flip(static::config('plural.irregular', false, [])));
        }
        if (!isset($compiled['merged']['uninflected'])) {
            $compiled['merged']['uninflected'] = array_merge(static::config('singular.uninflected', false, []), static::config('uninflected', false, []));
        }
        if (!isset($compiled['regex']['uninflected']) || !isset($compiled['regex']['irregular'])) {
            $compiled['regex']['uninflected'] = '(?:' . implode('|', $compiled['merged']['uninflected']) . ')';
            $compiled['regex']['irregular']   = '(?:' . implode('|', array_keys($compiled['merged']['irregular'])) . ')';
        }
        if (preg_match('/(.*)\\b(' . $compiled['regex']['irregular'] . ')$/i', $word, $regs)) {
            $cache[$word] = $regs[1] . $word[0] . substr($compiled['merged']['irregular'][strtolower($regs[2])], 1);
            return $cache[$word];
        }
        if (preg_match('/^(' . $compiled['regex']['uninflected'] . ')$/i', $word, $regs)) {
            $cache[$word] = $word;
            return $word;
        }
        foreach (static::config('singular.rules', false, []) as [$rule, $replacement]) {
            if (preg_match($rule, $word)) {
                $cache[$word] = preg_replace($rule, $replacement, $word);
                return $cache[$word];
            }
        }
        $cache[$word] = $word;
        return $word;
    }
}
