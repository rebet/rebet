<?php
namespace Rebet\Database\Ransack;

use Rebet\Common\Arrays;
use Rebet\Common\Callback;
use Rebet\Common\Strings;
use Rebet\Config\Configurable;
use Rebet\Database\Database;
use Rebet\Database\Exception\RansackException;

/**
 * Ransack Class
 *
 * This class support `Ransack Search` influenced by activerecord-hackery/ransack for Ruby.
 * Rebet's `Ransack Search` concept is much similar to that of Ruby, but there are differences in predicate keywords and features provided.
 *
 * Ransack Predicate format is `{col}[_{predicate}][_{compound}][_{option}]`.
 *
 * List of all possible predicates,
 *
 * No | Ransack Predicate  | Value Type   | Description                                                                 | Example emulated SQL
 * ---+--------------------+--------------+-----------------------------------------------------------------------------+------------------------------------------
 *  1 | All                | Blank        | If value is blank(null, '' or []) then ransack will be ignored              | ['name' => null] => (nothing)
 *  2 | integer            | 2D Array     | Join sub ransack conditions by 'OR'.                                        | [1 => [['name' => 'a', 'gender' => 1], ['name' => 'b', 'gender' => 2]]] => ((name = 'a' AND gender = 1) OR (name = 'b' AND gender = 2))
 *  3 | Any                | Any          | Custom predicate by Ransacker extension closure for each convert            | Anything you want
 *  4 | Any                | Any          | Custom predicate by Ransacker extension configure for all convert           | Anything you want
 *    | {col}_{predicate}  | Any          | Custom predicate by Ransack predicates configure for all convert            | Anything you want
 *    | {col}_eq           | Not Array    | Equals given value                                                          | ['name_eq'     => 'a'] => name = 'a'
 *    | {col}_not_eq       | Not Array    | Not equals given value                                                      | ['name_not_eq' => 'a'] => name <> 'a'
 *    | {col}_in           | Array        | Match any values in array                                                   | ['name_in'     => ['a', 'b']] => name IN ('a', 'b')
 *    | {col}_not_in       | Array        | NOT match any values in array                                               | ['name_not_in' => ['a', 'b']] => name NOT IN ('a', 'b')
 *    | {col}_lt           | Not Array    | Less than given value                                                       | ['age_lt'   => 20] => age < 20
 *    | {col}_lteq         | Not Array    | Less than or equals given value                                             | ['age_lteq' => 20] => age <= 20
 *    | {col}_gteq         | Not Array    | Grater than or equals given value                                           | ['age_gteq' => 20] => age >= 20
 *    | {col}_gt           | Not Array    | Grater than given value                                                     | ['age_gt'   => 20] => age > 20
 *    | {col}_from         | Not Array    | Alias of {col}_gteq                                                         | ['age_from' => 20] => age >= 20
 *    | {col}_to           | Not Array    | Alias of {col}_lteq                                                         | ['age_to'   => 20] => age <= 20
 *    | {col}_contains     | String       | Contains given value string                                                 | ['title_contains'     => '100%'] => title LIKE '%100|%%' ESCAPE '|'
 *    | {col}_not_contains | String       | Not contains given value string                                             | ['title_not_contains' => '100%'] => title NOT LIKE '%100|%%' ESCAPE '|'
 *    | {col}_starts       | String       | Starts with given value string                                              | ['title_starts'       => '100%'] => title LIKE '100|%%' ESCAPE '|'
 *    | {col}_not_starts   | String       | Not starts with given value string                                          | ['title_not_starts'   => '100%'] => title NOT LIKE '100|%%' ESCAPE '|'
 *    | {col}_ends         | String       | Ends with given value string                                                | ['title_ends'         => '100%'] => title LIKE '%100|%' ESCAPE '|'
 *    | {col}_not_ends     | String       | Not ends with given value string                                            | ['title_not_ends'     => '100%'] => title NOT LIKE '%100|%' ESCAPE '|'
 *    | {col}_matches      | String       | [mysql/pgsql] POSIX regex match                                             | ['title_matches'     => '^[0-9]+%'] => (mysql) title REGEXP '^[0-9]+%'    , (pgsql) title ~ '^[0-9]+%'
 *    | {col}_not_matches  | String       | [mysql/pgsql] POSIX regex not match                                         | ['title_not_matches' => '^[0-9]+%'] => (mysql) title NOT REGEXP '^[0-9]+%', (pgsql) title !~ '^[0-9]+%'
 *    | {col}_search       | String       | [mysql/pgsql] Full Text Search                                              | ['body_search' => 'foo'] => (mysql) MATCH(body) AGAINST('foo'), (pgsql) to_tsvector(body) @@ to_tsquery('foo')
 *    | {col}_null         | Not Blank    | Is null                                                                     | ['name_null'     => true] => name IS NULL
 *    | {col}_not_null     | Not Blank    | Is not null                                                                 | ['name_not_null' => true] => name IS NOT NULL
 *    | {col}_blank        | Not Blank    | Is null or empty                                                            | ['name_blank'    => true] => (name IS NULL OR name = '')
 *    | {col}_present      | Not Blank    | Is not null and not empty                                                   | ['name_present'  => true] => name IS NOT NULL AND name <> ''
 *    | *_any   (compound) | String/Array | Any compound multiple value join by 'OR'  (space separated string to array) | ['body_contains_any' => ['foo', 'bar']] => (body LIKE '%foo%' ESCAPE '|' OR body LIKE '%bar%' ESCAPE '|')
 *    | *_all   (compound) | String/Array | All compound multiple value join by 'AND' (space separated string to array) | ['body_contains_all' => 'foo bar']      => (body LIKE '%foo%' ESCAPE '|' AND body LIKE '%bar%' ESCAPE '|')
 *    | *_bin   (option)   | String       | [mysql/sqlite] Binary option depend on configure                            | ['body_contains_bin'   => 'foo'] => (sqlite/mysql) BINARY body LIKE '%foo%' ESCAPE '|'
 *    | *_cs    (option)   | String       | [mysql] Case sensitive option depend on configure                           | ['body_contains_cs'    => 'foo'] => (mysql) body COLLATE utf8mb4_bin LIKE '%foo%' ESCAPE '|'
 *    | *_ci    (option)   | String       | [mysql/sqlite] Case insensitive option depend on configure                  | ['body_contains_ci'    => 'foo'] => (sqlite) body COLLATE nocase LIKE '%foo%' ESCAPE '|', (mysql) body COLLATE utf8mb4_general_ci LIKE '%foo%' ESCAPE '|'
 *    | *_fuzzy (option)   | String       | [mysql] Fuzzy option depend on configure                                    | ['body_contains_fuzzy' => 'foo'] => (mysql) body COLLATE utf8mb4_unicode_ci LIKE '%foo%' ESCAPE '|'
 *    | {col}              | Array        | Short predicates of {col}_in                                                | ['name' => ['a', 'b']] => name IN ('a', 'b')
 *    | {col}              | Not Array    | Short predicates of {col}_eq                                                | ['name' => 'a'] => name = 'a'
 * ---+--------------------+--------------+-----------------------------------------------------------------------------+------------------------------------------
 *
 * Rebet's `Ransack Search` does not support `_and_` and `_or_` conjunctions, but support multiple column aliases.
 * When multiple columns is given by alias, it will be connected by a configured conjunction.
 * NOTE: Usually, default conjunction is `OR` when the predicate was not included `_not_` and become `AND` if include `_not_`.
 *       However, this is reversed for some predicates such as `_null` and` _blank`.
 *       For details, check the predicates setting in the Ransack class.
 *
 *  $ransacker->convert('name_contains', 'John', ['name' => ['first_name', 'last_name']]);
 *  # => (first_name LIKE '%John%' OR last_name LIKE '%John%')
 *
 * If you give predicate includes '_not_' then multiple column alias will be conjunct by `AND`, like below.
 *
 *  $ransacker->convert('name_not_contains', 'John', ['name' => ['first_name', 'last_name']]);
 *  # => (first_name NOT LIKE '%John%' AND last_name NOT LIKE '%John%')
 *
 * However, in the above examples, when searching with the full name 'John Smith', the intended data may not be hit, and this behavior may not be desirable.
 * Even in such a case, you can achieve the purpose by defining the alias as follows:
 *
 *  $ransacker->convert('name_contains', 'John Smith', ['name' => "CONCAT(first_name, ' ', last_name)"]);
 *  # => CONCAT(first_name, ' ', last_name) LIKE '%John Smith%'
 *
 * These definition methods can be used in combination and you can reuse other defined aliases by specifying "@ + alias_name" as follows:
 *
 *  $ransacker->convert('freeword_contains_fuzzy', 'John', ['freeword' => ['@author_name', 'title', 'body'], 'author_name' => "CONCAT(author_first_name, ' ', author_last_name)"]);
 *  # => (CONCAT(author_first_name, ' ', author_last_name) COLLATE utf8mb4_unicode_ci LIKE '%John%' OR title COLLATE utf8mb4_unicode_ci LIKE '%John%' OR body COLLATE utf8mb4_unicode_ci LIKE '%John%')
 *
 * @see https://github.com/activerecord-hackery/ransack
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Ransack
{
    use Configurable;

    public static function defaultConfig()
    {
        $ignore_converter   = function ($value) { return null; };
        $contains_converter = function ($value) { return '%'.str_replace(['|', '%', '_'], ['||', '|%', '|_'], $value).'%'; };
        $starts_converter   = function ($value) { return     str_replace(['|', '%', '_'], ['||', '|%', '|_'], $value).'%'; };
        $ends_converter     = function ($value) { return '%'.str_replace(['|', '%', '_'], ['||', '|%', '|_'], $value)    ; };
        return [
            'compound_separator' => '/[\s　]/',
            'predicates'         => [
                // predicate => [template, value_converter, multiple_columns_conjunction]
                'common' => [
                    'eq'           => ["{col} = {val}"                           , null               , 'OR' ],
                    'not_eq'       => ["{col} <> {val}"                          , null               , 'AND'],
                    'in'           => ["{col} IN ({val})"                        , null               , 'OR' ],
                    'not_in'       => ["{col} NOT IN ({val})"                    , null               , 'AND'],
                    'lt'           => ["{col} < {val}"                           , null               , 'OR' ],
                    'lteq'         => ["{col} <= {val}"                          , null               , 'OR' ],
                    'gteq'         => ["{col} >= {val}"                          , null               , 'OR' ],
                    'gt'           => ["{col} > {val}"                           , null               , 'OR' ],
                    'from'         => ["{col} >= {val}"                          , null               , 'OR' ],
                    'to'           => ["{col} <= {val}"                          , null               , 'OR' ],
                    'contains'     => ["{col} LIKE {val} ESCAPE '|'"             , $contains_converter, 'OR' ],
                    'not_contains' => ["{col} NOT LIKE {val} ESCAPE '|'"         , $contains_converter, 'AND'],
                    'starts'       => ["{col} LIKE {val} ESCAPE '|'"             , $starts_converter  , 'OR' ],
                    'not_starts'   => ["{col} NOT LIKE {val} ESCAPE '|'"         , $starts_converter  , 'AND'],
                    'ends'         => ["{col} LIKE {val} ESCAPE '|'"             , $ends_converter    , 'OR' ],
                    'not_ends'     => ["{col} NOT LIKE {val} ESCAPE '|'"         , $ends_converter    , 'AND'],
                    'null'         => ["{col} IS NULL"                           , $ignore_converter  , 'AND'],
                    'not_null'     => ["{col} IS NOT NULL"                       , $ignore_converter  , 'OR' ],
                    'blank'        => ["({col} IS NULL OR {col} = '')"           , $ignore_converter  , 'AND'],
                    'not_blank'    => ["({col} IS NOT NULL AND {col} <> '')"     , $ignore_converter  , 'OR' ],
                ],
                'sqlite' => [
                    'matches'      => ["{col} REGEXP {val}"                      , null               , 'OR' ],
                    'not_matches'  => ["{col} NOT REGEXP {val}"                  , null               , 'AND'],
                    'search'       => ["{col} MATCH {val}"                       , null               , 'OR' ],
                ],
                'mysql' => [
                    'matches'      => ["{col} REGEXP {val}"                      , null               , 'OR' ],
                    'not_matches'  => ["{col} NOT REGEXP {val}"                  , null               , 'AND'],
                    'search'       => ["MATCH({col}) AGAINST({val})"             , null               , 'OR' ],
                ],
                'pgsql' => [
                    'matches'      => ["{col} ~ {val}"                           , null               , 'OR' ],
                    'not_matches'  => ["{col} !~ {val}"                          , null               , 'AND'],
                    'search'       => ["to_tsvector({col}) @@ to_tsquery({val})" , null               , 'OR' ],
                ],
            ],
            'options' => [
                'sqlite' => [
                    'bin'   => 'BINARY {col}',
                    'ci'    => '{col} COLLATE nocase',
                ],
                'mysql' => [
                    'bin'   => 'BINARY {col}',
                    'cs'    => '{col} COLLATE utf8mb4_bin',
                    'ci'    => '{col} COLLATE utf8mb4_general_ci',
                    'fuzzy' => '{col} COLLATE utf8mb4_unicode_ci',
                ],
                'pgsql' => [
                    // Currentry option is nothing.
                ],
            ],
        ];
    }

    /**
     * Original ransack predicate
     *
     * @var string
     */
    protected $origin;

    /**
     * Analyzed value
     *
     * @var string
     */
    protected $value;

    /**
     * Analyzed predicate
     *
     * @var string
     */
    protected $predicate;

    /**
     * Analyzed template
     *
     * @var string
     */
    protected $template;

    /**
     * Analyzed value converter
     *
     * @var \Closure|null
     */
    protected $value_converter;

    /**
     * Analyzed conjunction
     *
     * @var string 'AND'|'OR'
     */
    protected $conjunction;

    /**
     * Analyzed compound
     *
     * @var string|null 'any'|'all'
     */
    protected $compound;

    /**
     * Analyzed option
     *
     * @var string|null
     */
    protected $option;

    /**
     * Analyzed columns
     *
     * @var array
     */
    protected $columns;

    /**
     * Create predicate
     *
     * @param string $origin
     * @param mixed $value
     * @param string $predicate
     * @param string $template
     * @param \Closure|null $value_converter
     * @param array $columns
     * @param string|null $compound
     * @param string $conjunction
     * @param string|null $option
     */
    protected function __construct(string $origin, $value, string $predicate, string $template, ?\Closure $value_converter, array $columns, string $conjunction, ?string $compound, ?string $option)
    {
        $this->origin          = $origin;
        $this->value           = $value;
        $this->predicate       = $predicate;
        $this->template        = $template;
        $this->value_converter = $value_converter;
        $this->columns         = $columns;
        $this->conjunction     = $conjunction;
        $this->compound        = $compound;
        $this->option          = $option;
    }

    /**
     * Analyze given ransack predicate.
     *
     * @param Database $db
     * @param string $ransack_predicate
     * @param mixed $value
     * @param array $alias
     * @return self
     */
    public static function analyze(Database $db, string $ransack_predicate, $value, array $alias = []) : self
    {
        $origin      = $ransack_predicate;
        $option      = null;
        $driver_name = $db->driverName();
        foreach (static::config("options.{$driver_name}", false, []) as $o => $ot) {
            if (Strings::endsWith($ransack_predicate, "_{$o}")) {
                $ransack_predicate = Strings::rtrim($ransack_predicate, "_{$o}");
                $option            = $ot;
                break;
            }
        }

        $compound = null;
        foreach (['any', 'all'] as $c) {
            if (Strings::endsWith($ransack_predicate, "_{$c}")) {
                $ransack_predicate = Strings::rtrim($ransack_predicate, "_{$c}");
                $compound          = $c;
                break;
            }
        }

        $predicates = Arrays::sortKeys(array_merge(static::config("predicates.common", false, []), static::config("predicates.{$driver_name}", false, [])), SORT_DESC, Callback::compareLength());
        $predicate  = null;
        foreach ($predicates as $p => [$t, $vc, $c]) {
            if (Strings::endsWith($ransack_predicate, "_{$p}")) {
                $ransack_predicate = Strings::rtrim($ransack_predicate, "_{$p}");
                $template          = $t;
                $value_converter   = $vc;
                $predicate         = $p;
                $conjunction       = $c;
                break;
            }
        }

        if ($predicate === null) {
            $predicate       = is_array($value) ? 'in' : 'eq' ;
            $template        = $predicates[$predicate][0];
            $value_converter = $predicates[$predicate][1];
            $conjunction     = $predicates[$predicate][2];
            if ($compound) {
                throw RansackException::by("Short predicates of 'in' and 'eq' can not contain 'any' and 'all' compound word.");
            };
        }

        $columns = static::resolveAlias($ransack_predicate, $alias);
        return new static($origin, $value, $predicate, $template, $value_converter, $columns, $conjunction, $compound, $option);
    }

    /**
     * Resolve alias column name
     *
     * @param string $column
     * @param array $alias
     * @return array
     */
    protected static function resolveAlias(string $column, array $alias) : array
    {
        $columns = [];
        foreach (Arrays::toArray($alias[$column] ?? $column) as $column) {
            if (Strings::startsWith($column, '@')) {
                $columns = array_merge($columns, static::resolveAlias(Strings::ltrim($column, '@'), $alias));
            } else {
                $columns[] = $column;
            }
        }
        return $columns;
    }

    /**
     * Get original ransack predicate
     *
     * @return string
     */
    public function origin() : string
    {
        return $this->origin;
    }

    /**
     * Get original value or converted value for this predicate.
     *
     * @param bool $convert value or not (default: false)
     * @param \Closure|null $value_converter (default: null)
     * @return void
     */
    public function value(bool $convert = false, ?\Closure $value_converter = null)
    {
        if (!$convert) {
            return $this->value;
        }

        $value = $this->value;
        if ($this->compound) {
            $value = is_array($value) ? $value : array_values(Arrays::compact(preg_split(static::config('compound_separator'), $value))) ;
        }

        $value_converter = $value_converter ?? $this->value_converter ;
        if ($value_converter) {
            return $this->compound ? array_map(function ($v) use ($value_converter, $value) { return $value_converter($v); }, $value) : $value_converter($value) ;
        }
        return $this->value ;
    }

    /**
     * Get analyzed predicate
     *
     * @return string
     */
    public function predicate() : string
    {
        return $this->predicate;
    }

    /**
     * Get template of this predicate.
     * This template include placeholder '{col}' and '{val}'.
     *
     * @return string
     */
    public function template() : string
    {
        return $this->template;
    }

    /**
     * Get value converter of this predicate.
     *
     * @return \Closure|null
     */
    public function valueConverter() : ?\Closure
    {
        return $this->value_converter;
    }

    /**
     * Get multiple column conjunction word ('AND' or 'OR') of this predicate.
     *
     * @return string 'AND'|'OR'
     */
    public function conjunction() : string
    {
        return $this->conjunction;
    }

    /**
     * Get compound word ('any' or 'all') of this predicate.
     *
     * @return string|null 'any'|'all'
     */
    public function compound() : ?string
    {
        return $this->compound;
    }

    /**
     * Get option template of this predicate.
     * This template include placeholder '{col}'.
     *
     * @return string|null
     */
    public function option() : ?string
    {
        return $this->option;
    }

    /**
     * Get columns that aliases resolved.
     * You can choose apply option or not.
     *
     * @param boolean $apply_option (default: true)
     * @return array
     */
    public function columns(bool $apply_option = true) : array
    {
        if (!$apply_option) {
            return $this->columns;
        }
        return $this->option ? array_map(function ($v) { return str_replace('{col}', $v, $this->option); }, $this->columns) : $this->columns ;
    }

    /**
     * Convert ransack to SQL where and params using given template and value converter.
     * If just call convert() without arguments then use default template and value converter.
     *
     * @param string|null $template (default: null)
     * @param \Closure|null $value_converter (default: null)
     * @return array
     */
    public function convert(?string $template = null, ?\Closure $value_converter = null) : array
    {
        $template        = $template ?? $this->template;
        $params          = [];
        $wheres          = [];
        $columns         = $this->columns();
        $columns_count   = count($columns);
        $values          = $this->compound ? $this->value(true, $value_converter) : [$this->value(true, $value_converter)];
        $values_count    = count($values);

        foreach ($values as $i => $value) {
            $idx_i      = $values_count === 1 ? "" : "_{$i}";
            $sub_wheres = [];
            foreach ($columns as $j => $column) {
                $idx_j        = $columns_count === 1 ? "" : "_{$j}";
                $key          = "{$this->origin}{$idx_i}{$idx_j}";
                $sub_wheres[] = str_replace(['{col}', '{val}'], [$column, ":{$key}"], $template);
                if ($value !== null) {
                    $params[$key] = $value ;
                }
            }
            $wheres[] = count($sub_wheres) === 1 ? $sub_wheres[0] : '('.implode(" {$this->conjunction} ", $sub_wheres).')' ;
        }
        $sql = count($wheres) === 1 ? $wheres[0] : '('.implode($this->compound === 'any' ? ' OR ' : ' AND ', $wheres).')' ;

        return [$sql, $params];
    }
}
