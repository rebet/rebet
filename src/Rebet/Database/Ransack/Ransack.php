<?php
namespace Rebet\Database\Ransack;

use Rebet\Common\Arrays;
use Rebet\Common\Callback;
use Rebet\Common\Strings;
use Rebet\Common\Utils;
use Rebet\Config\Configurable;
use Rebet\Database\Condition;
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
 * | No | Ransack Predicate   | Value Type   | Description ([LMP] means only support L=sqlite, M=mysql, P=pgsql)           | Example emulated SQL
 * +----+---------------------+--------------+-----------------------------------------------------------------------------+------------------------------------------
 * |  1 | All                 | Blank        | If value is blank(null, '' or []) then ransack will be ignored              | ['name' => null] => (nothing)
 * |  2 | integer             | 2D Array     | Join sub ransack conditions by 'OR'.                                        | [1 => [['name' => 'a', 'gender' => 1], ['name' => 'b', 'gender' => 2]]] => ((name = 'a' AND gender = 1) OR (name = 'b' AND gender = 2))
 * |  3 | Any                 | Any          | Custom predicate by Ransack extension closure for each resolve              | Anything you want
 * +----+---------------------+--------------+-----------------------------------------------------------------------------+------------------------------------------
 * |  4 | {col}_{predicate}   | Any          | Custom predicate by Ransack `predicates` configure for all convert          | Anything you want
 * |    | {col}_eq            | Not Array    | Equals given value                                                          | ['name_eq'     => 'a'] => name = 'a'
 * |    | {col}_not_eq        | Not Array    | Not equals given value                                                      | ['name_not_eq' => 'a'] => name <> 'a'
 * |    | {col}_in            | Array        | Match any values in array                                                   | ['name_in'     => ['a', 'b']] => name IN ('a', 'b')
 * |    | {col}_not_in        | Array        | NOT match any values in array                                               | ['name_not_in' => ['a', 'b']] => name NOT IN ('a', 'b')
 * |    | {col}_lt            | Not Array    | Less than given value                                                       | ['age_lt'   => 20] => age < 20
 * |    | {col}_lteq          | Not Array    | Less than or equals given value                                             | ['age_lteq' => 20] => age <= 20
 * |    | {col}_gteq          | Not Array    | Grater than or equals given value                                           | ['age_gteq' => 20] => age >= 20
 * |    | {col}_gt            | Not Array    | Grater than given value                                                     | ['age_gt'   => 20] => age > 20
 * |    | {col}_from          | Not Array    | Alias of {col}_gteq                                                         | ['age_from' => 20] => age >= 20
 * |    | {col}_to            | Not Array    | Alias of {col}_lteq                                                         | ['age_to'   => 20] => age <= 20
 * |    | {col}_contains      | String       | Contains given value string                                                 | ['title_contains'     => '100%'] => title LIKE '%100|%%' ESCAPE '|'
 * |    | {col}_not_contains  | String       | Not contains given value string                                             | ['title_not_contains' => '100%'] => title NOT LIKE '%100|%%' ESCAPE '|'
 * |    | {col}_starts        | String       | Starts with given value string                                              | ['title_starts'       => '100%'] => title LIKE '100|%%' ESCAPE '|'
 * |    | {col}_not_starts    | String       | Not starts with given value string                                          | ['title_not_starts'   => '100%'] => title NOT LIKE '100|%%' ESCAPE '|'
 * |    | {col}_ends          | String       | Ends with given value string                                                | ['title_ends'         => '100%'] => title LIKE '%100|%' ESCAPE '|'
 * |    | {col}_not_ends      | String       | Not ends with given value string                                            | ['title_not_ends'     => '100%'] => title NOT LIKE '%100|%' ESCAPE '|'
 * |    | {col}_matches       | String       | [LMP] POSIX regex match     (need extension when sqlite)                    | ['title_matches'     => '^[0-9]+%'] => [LM] title REGEXP '^[0-9]+%'    , [P] title ~ '^[0-9]+%'
 * |    | {col}_not_matches   | String       | [LMP] POSIX regex not match (need extension when sqlite)                    | ['title_not_matches' => '^[0-9]+%'] => [LM] title NOT REGEXP '^[0-9]+%', [P] title !~ '^[0-9]+%'
 * |    | {col}_search        | String       | [LMP] Full Text Search      (need extension when sqlite)                    | ['body_search' => 'foo'] => [L] body MATCH 'foo', [M] MATCH(body) AGAINST('foo'), [P] to_tsvector(body) @@ to_tsquery('foo')
 * |    | {col}_null          | Not Blank    | Is null                                                                     | ['name_null'     => true] => name IS NULL
 * |    | {col}_not_null      | Not Blank    | Is not null                                                                 | ['name_not_null' => true] => name IS NOT NULL
 * |    | {col}_blank         | Not Blank    | Is null or empty                                                            | ['name_blank'    => true] => (name IS NULL OR name = '')
 * |    | {col}_present       | Not Blank    | Is not null and not empty                                                   | ['name_present'  => true] => name IS NOT NULL AND name <> ''
 * |    | {col}               | Array        | Short predicates of {col}_in                                                | ['name' => ['a', 'b']] => name IN ('a', 'b')
 * |    | {col}               | Not Array    | Short predicates of {col}_eq                                                | ['name' => 'a'] => name = 'a'
 * +    +---------------------+--------------+-----------------------------------------------------------------------------+------------------------------------------
 * |    | *_any    (compound) | String/Array | Any compound multiple value join by 'OR'  (space separated string to array) | ['body_contains_any' => ['foo', 'bar']] => (body LIKE '%foo%' ESCAPE '|' OR body LIKE '%bar%' ESCAPE '|')
 * |    | *_all    (compound) | String/Array | All compound multiple value join by 'AND' (space separated string to array) | ['body_contains_all' => 'foo bar']      => (body LIKE '%foo%' ESCAPE '|' AND body LIKE '%bar%' ESCAPE '|')
 * +    +---------------------+--------------+-----------------------------------------------------------------------------+------------------------------------------
 * |    | *_{option} (option) | Any          | [any] Custom option by Ransack `options` configure for all convert          | Anything you want
 * |    | *_bin      (option) | String       | [LM ] Binary option depend on configure                                     | ['body_contains_bin' => 'foo'] => [LM] BINARY body LIKE '%foo%' ESCAPE '|'
 * |    | *_cs       (option) | String       | [ M ] Case sensitive option depend on configure                             | ['body_contains_cs'  => 'foo'] => [M] body COLLATE utf8mb4_bin LIKE '%foo%' ESCAPE '|'
 * |    | *_ci       (option) | String       | [LM ] Case insensitive option depend on configure                           | ['body_contains_ci'  => 'foo'] => [L] body COLLATE nocase LIKE '%foo%' ESCAPE '|', [M] body COLLATE utf8mb4_general_ci LIKE '%foo%' ESCAPE '|'
 * |    | *_fs       (option) | String       | [ M ] Fuzzy search option depend on configure                               | ['body_contains_fs'  => 'foo'] => [M] body COLLATE utf8mb4_unicode_ci LIKE '%foo%' ESCAPE '|'
 * |    | *_len      (option) | String       | [LMP] Length option depend on configure                                     | ['tag_eq_len' => 3    ] => [LP] LENGTH(tag) = 3, [M] CHAR_LENGTH(tag) = 3
 * |    | *_uc       (option) | String       | [LMP] Upper case option depend on configure                                 | ['tag_eq_uc'  => 'FOO'] => [LMP] UPPER(tag) = 'FOO'
 * |    | *_lc       (option) | String       | [LMP] Lower case option depend on configure                                 | ['tag_eq_lc'  => 'foo'] => [LMP] LOWER(tag) = 'foo'
 * |    | *_age      (option) | DateTime     | [LMP] Age option depend on configure                                        | ['birthday_lt_age' =>   20] => [L] CAST((STRFTIME('%Y%m%d', 'now') - STRFTIME('%Y%m%d', birthday)) / 10000 AS int) < 20, [M] TIMESTAMPDIFF(YEAR, birthday, CURRENT_DATE) < 20, [P] DATE_PART('year', AGE(birthday)) < 20
 * |    | *_y        (option) | DateTime     | [LMP] Year of date time option depend on configure                          | ['entry_at_eq_y'   => 2000] => [L] STRFTIME('%Y', entry_at) = 2000, [M] YEAR(entry_at) = 2000  , [P] DATE_PART('year', entry_at) = 2000
 * |    | *_m        (option) | DateTime     | [LMP] Month of date time option depend on configure                         | ['entry_at_eq_m'   =>   12] => [L] STRFTIME('%m', entry_at) = 12  , [M] MONTH(entry_at) = 12   , [P] DATE_PART('month', entry_at) = 12
 * |    | *_d        (option) | DateTime     | [LMP] Day of date time option depend on configure                           | ['entry_at_eq_d'   =>   12] => [L] STRFTIME('%d', entry_at) = 12  , [M] DAY(entry_at) = 12     , [P] DATE_PART('day', entry_at) = 12
 * |    | *_h        (option) | DateTime     | [LMP] Hour of date time option depend on configure                          | ['entry_at_eq_h'   =>   12] => [L] STRFTIME('%H', entry_at) = 12  , [M] HOUR(entry_at) = 12    , [P] DATE_PART('hour', entry_at) = 12
 * |    | *_i        (option) | DateTime     | [LMP] Minute of date time option depend on configure                        | ['entry_at_eq_i'   =>   12] => [L] STRFTIME('%M', entry_at) = 12  , [M] MINUTE(entry_at) = 12  , [P] DATE_PART('minute', entry_at) = 12
 * |    | *_s        (option) | DateTime     | [LMP] Second of date time option depend on configure                        | ['entry_at_eq_s'   =>   12] => [L] STRFTIME('%S', entry_at) = 12  , [M] SECOND(entry_at) = 12  , [P] DATE_PART('second', entry_at) = 12
 * |    | *_dow      (option) | DateTime     | [LMP] Day of week option depend on configure                                | ['entry_at_eq_dow' =>    1] => [L] STRFTIME('%w', entry_at) = 1   , [M] DAYOFWEEK(entry_at) = 1, [P] DATE_PART('dow', entry_at) = 1
 * +----+----------------+--------------+-----------------------------------------------------------------------------+------------------------------------------
 *
 * Rebet's `Ransack Search` does not support `_and_` and `_or_` conjunctions, but support multiple column aliases.
 * When multiple columns is given by alias, it will be connected by a configured conjunction.
 * NOTE: Usually, default conjunction is `OR` when the predicate was not included `_not_` and become `AND` if include `_not_`.
 *       However, this is reversed for some predicates such as `_null` and` _blank`.
 *       For details, check the predicates setting in the Ransack class.
 *
 *  $ransack->convert('name_contains', 'John', ['name' => ['first_name', 'last_name']]);
 *  # => (first_name LIKE '%John%' ESCAPE '|' OR last_name LIKE '%John%' ESCAPE '|')
 *
 * If you give predicate includes '_not_' then multiple column alias will be conjunct by `AND`, like below.
 *
 *  $ransack->convert('name_not_contains', 'John', ['name' => ['first_name', 'last_name']]);
 *  # => (first_name NOT LIKE '%John%' ESCAPE '|' AND last_name NOT LIKE '%John%' ESCAPE '|')
 *
 * However, in the above examples, when searching with the full name 'John Smith', the intended data may not be hit, and this behavior may not be desirable.
 * Even in such a case, you can achieve the purpose by defining the alias as follows:
 *
 *  $ransack->convert('name_contains', 'John Smith', ['name' => "CONCAT(first_name, ' ', last_name)"]);
 *  # => CONCAT(first_name, ' ', last_name) LIKE '%John Smith% ESCAPE '|''
 *
 * These definition methods can be used in combination and you can reuse other defined aliases by specifying "@ + alias_name" as follows:
 *
 *  $ransack->convert('freeword_contains_fs', 'John', ['freeword' => ['@author_name', 'title', 'body'], 'author_name' => "CONCAT(author_first_name, ' ', author_last_name)"]);
 *  # => (CONCAT(author_first_name, ' ', author_last_name) COLLATE utf8mb4_unicode_ci LIKE '%John%' ESCAPE '|' OR title COLLATE utf8mb4_unicode_ci LIKE '%John%' ESCAPE '|' OR body COLLATE utf8mb4_unicode_ci LIKE '%John%' ESCAPE '|')
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
                    'bin' => 'BINARY {col}',
                    'ci'  => '{col} COLLATE nocase',
                    'len' => 'LENGTH({col})',
                    'uc'  => 'UPPER({col})',
                    'lc'  => 'LOWER({col})',
                    'age' => "CAST((STRFTIME('%Y%m%d', 'now') - STRFTIME('%Y%m%d', {col})) / 10000 AS int)",
                    'y'   => "STRFTIME('%Y', {col})",
                    'm'   => "STRFTIME('%m', {col})",
                    'd'   => "STRFTIME('%d', {col})",
                    'h'   => "STRFTIME('%H', {col})",
                    'i'   => "STRFTIME('%M', {col})",
                    's'   => "STRFTIME('%S', {col})",
                    'dow' => "STRFTIME('%w', {col})",
                ],
                'mysql' => [
                    'bin' => 'BINARY {col}',
                    'cs'  => '{col} COLLATE utf8mb4_bin',
                    'ci'  => '{col} COLLATE utf8mb4_general_ci',
                    'fs'  => '{col} COLLATE utf8mb4_unicode_ci',
                    'len' => 'CHAR_LENGTH({col})',
                    'uc'  => 'UPPER({col})',
                    'lc'  => 'LOWER({col})',
                    'age' => 'TIMESTAMPDIFF(YEAR, {col}, CURRENT_DATE)',
                    'y'   => 'YEAR({col})',
                    'm'   => 'MONTH({col})',
                    'd'   => 'DAY({col})',
                    'h'   => 'HOUR({col})',
                    'i'   => 'MINUTE({col})',
                    's'   => 'SECOND({col})',
                    'dow' => 'DAYOFWEEK({col})',
                ],
                'pgsql' => [
                    'len' => 'LENGTH({col})',
                    'uc'  => 'UPPER({col})',
                    'lc'  => 'LOWER({col})',
                    'age' => "DATE_PART('year', AGE({col}))",
                    'y'   => "DATE_PART('year', {col})",
                    'm'   => "DATE_PART('month', {col})",
                    'd'   => "DATE_PART('day', {col})",
                    'h'   => "DATE_PART('hour', {col})",
                    'i'   => "DATE_PART('minute', {col})",
                    's'   => "DATE_PART('second', {col})",
                    'dow' => "DATE_PART('dow', {col})",
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
     * Placeholder suffix.
     *
     * @var string
     */
    protected $placeholder_suffix;

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
     * @param string $placeholder_suffix (default: '')
     */
    protected function __construct(string $origin, $value, string $predicate, string $template, ?\Closure $value_converter, array $columns, string $conjunction, ?string $compound, ?string $option, string $placeholder_suffix = '')
    {
        $this->origin             = $origin;
        $this->value              = $value;
        $this->predicate          = $predicate;
        $this->template           = $template;
        $this->value_converter    = $value_converter;
        $this->columns            = $columns;
        $this->conjunction        = $conjunction;
        $this->compound           = $compound;
        $this->option             = $option;
        $this->placeholder_suffix = $placeholder_suffix;
    }

    /**
     * Resolve given ransack predicate.
     *
     * @param Database $db
     * @param int|string $ransack_predicate
     * @param mixed $value
     * @param array $alias (default: [])
     * @param \Closure|null $extension function(Database $db, Ransack $ransack) : Condition { ... } (default: null)
     * @param string $placeholder_suffix (default: '')
     * @return Condition|null
     */
    public static function resolve(Database $db, $ransack_predicate, $value, array $alias = [], ?\Closure $extension = null, string $placeholder_suffix = '') : ?Condition
    {
        //  1 | If value is blank(null, '' or []) then ransack will be ignored
        if (Utils::isBlank($value)) {
            return null;
        }

        //  2 | Join sub ransack conditions by 'OR'.
        if (is_int($ransack_predicate)) {
            $where  = [];
            $params = [];
            foreach ($value as $i => $sub_conditions) {
                $sub_where  = [];
                $sub_params = [];
                foreach ($sub_conditions as $k => $v) {
                    if ($condition = static::resolve($db, $k, $v, $alias, $extension, "{$placeholder_suffix}_{$i}")) {
                        $sub_where[] = $condition->sql;
                        $sub_params  = array_merge($sub_params, $condition->params);
                    }
                }
                $where[] = '('.implode(' AND ', $sub_where).')';
                $params  = array_merge($params, $sub_params);
            }
            return new Condition('('.implode(' OR ', $where).')', $params);
        }

        $ransack = static::analyze($db, $ransack_predicate, $value, $alias, $placeholder_suffix);

        //  3 | Custom predicate by ransack extension closure for each convert
        if ($extension && $custom = $extension($db, $ransack)) {
            return $custom;
        }

        //  4 | Ransack convert based on configure
        return $ransack->convert();
    }

    /**
     * Analyze given ransack predicate.
     *
     * @param Database $db
     * @param string $ransack_predicate
     * @param mixed $value
     * @param array $alias (default: [])
     * @param string $placeholder_suffix (default: '')
     * @return self
     */
    public static function analyze(Database $db, string $ransack_predicate, $value, array $alias = [], string $placeholder_suffix = '') : self
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
        return new static($origin, $value, $predicate, $template, $value_converter, $columns, $conjunction, $compound, $option, $placeholder_suffix);
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
     * @param \Closure|null $value_converter function(mixed $value) { ... } (default: null)
     * @return mixed
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
     * Get placeholder suffix of this predicate.
     *
     * @return string
     */
    public function placeholderSuffix() : string
    {
        return $this->placeholder_suffix;
    }

    /**
     * Convert ransack to SQL where and params using given template and value converter.
     * If just call convert() without arguments then use default template and value converter.
     *
     * @param string|null $template (default: null)
     * @param \Closure|null $value_converter function(mixed $value) { ... } (default: null)
     * @return Condition
     */
    public function convert(?string $template = null, ?\Closure $value_converter = null) : Condition
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
                $key          = "{$this->origin}{$this->placeholder_suffix}{$idx_i}{$idx_j}";
                $sub_wheres[] = str_replace(['{col}', '{val}'], [$column, ":{$key}"], $template);
                if ($value !== null) {
                    $params[$key] = $value ;
                }
            }
            $wheres[] = count($sub_wheres) === 1 ? $sub_wheres[0] : '('.implode(" {$this->conjunction} ", $sub_wheres).')' ;
        }
        $sql = count($wheres) === 1 ? $wheres[0] : '('.implode($this->compound === 'any' ? ' OR ' : ' AND ', $wheres).')' ;

        return new Condition($sql, $params);
    }
}
