<?php
namespace Rebet\Database\Ransack;

use Rebet\Common\Utils;
use Rebet\Database\Database;

/**
 * Builtin Ransacker Interface
 *
 * This class support 'Ransack Search' influenced by activerecord-hackery/ransack for Ruby.
 * Rebet's 'Ransack Search' concept is much similar to that of Ruby, but there are differences in predicate keywords and features provided.
 *
 * List of all possible predicates,
 *
 * No | Predicate             | Value Type | Description                                                         | Example emulated SQL
 * ---------------------------------------------------------------------------------------------------------------------------------------------------------
 *  1 | *                     | Blank      | If value is blank(null, '' and []) then ransak will be ignored      | ['name' => null] => (empty string)
 *  2 | Any                   | Any        | Custom predicate by ransack extention closure                       | Anything you want
 *    | {col}_eq              | Not Array  | Equals given value                                                  | ['name_eq'     => 'a'] => name = 'a'
 *    | {col}_not_eq          | Not Array  | Not equals given value                                              | ['name_not_eq' => 'a'] => name <> 'a'
 *    | {col}_in              | Array      | Match any values in array                                           | ['name_in'     => ['a', 'b']] => name IN ('a', 'b')
 *    | {col}_not_in          | Array      | NOT match any values in array                                       | ['name_not_in' => ['a', 'b']] => name NOT IN ('a', 'b')
 *    | {col}_lt              | Not Array  | Less than given value                                               | ['age_lt'   => 20] => age < 20
 *    | {col}_lteq            | Not Array  | Less than or equals given value                                     | ['age_lteq' => 20] => age <= 20
 *    | {col}_gteq            | Not Array  | Grater than or equals given value                                   | ['age_gteq' => 20] => age >= 20
 *    | {col}_gt              | Not Array  | Grater than given value                                             | ['age_gt'   => 20] => age > 20
 *    | {col}_from            | Not Array  | Alias of {col}_gteq                                                 | ['age_from' => 20] => age >= 20
 *    | {col}_to              | Not Array  | Alias of {col}_lteq                                                 | ['age_to'   => 20] => age <= 20
 *    | {col}_contains        | String     | Contains given value string                                         | ['title_contains'     => '100%'] => title LIKE '%100|%%' ESCAPE '|'
 *    | {col}_not_contains    | String     | Not contains given value string                                     | ['title_not_contains' => '100%'] => title NOT LIKE '%100|%%' ESCAPE '|'
 *    | {col}_starts          | String     | Starts with given value string                                      | ['title_starts'       => '100%'] => title LIKE '100|%%' ESCAPE '|'
 *    | {col}_not_starts      | String     | Not starts with given value string                                  | ['title_not_starts'   => '100%'] => title NOT LIKE '100|%%' ESCAPE '|'
 *    | {col}_ends            | String     | Ends with given value string                                        | ['title_ends'         => '100%'] => title LIKE '%100|%' ESCAPE '|'
 *    | {col}_not_ends        | String     | Not ends with given value string                                    | ['title_not_ends'     => '100%'] => title NOT LIKE '%100|%' ESCAPE '|'
 *    | {col}_matches         | String     | [mysql/pgsql] POSIX regex match                                     | ['title_matches'     => '^[0-9]+%'] => (mysql) title REGEXP '^[0-9]+%'    , (pgsql) title ~ '^[0-9]+%'
 *    | {col}_not_matches     | String     | [mysql/pgsql] POSIX regex not match                                 | ['title_not_matches' => '^[0-9]+%'] => (mysql) title NOT REGEXP '^[0-9]+%', (pgsql) title !~ '^[0-9]+%'
 *    | {col}_search          | String     | [mysql/pgsql] Full Text Search                                      | ['body_search' => 'foo'] => (mysql) MATCH(body) AGAINST('foo'), (pgsql) to_tsvector(body) @@ to_tsquery('foo')
 *    | {col}_null            | Not Blank  | Is null                                                             | ['name_null'     => true] => name IS NULL
 *    | {col}_not_null        | Not Blank  | Is not null                                                         | ['name_not_null' => true] => name IS NOT NULL
 *    | {col}_blank           | Not Blank  | Is null or empty                                                    | ['name_blank'    => true] => (name IS NULL OR name = '')
 *    | {col}_present         | Not Blank  | Is not null and not empty                                           | ['name_present'  => true] => name IS NOT NULL AND name <> ''
 *    | integer               | 2D Array   | Join sub ransack conditions by 'OR'.                                | [1 => [['name' => 'a', 'gender' => 1], ['name' => 'b', 'gender' => 2]]] => ((name = 'a' AND gender = 1) OR (name = 'b' AND gender = 2))
 *    | {col}                 | Array      | Short predicates of {col}_in                                        | ['name' => ['a', 'b']] => name IN ('a', 'b')
 *    | {col}                 | Not Array  | Short predicates of {col}_equals                                    | ['name' => 'a'] => name = 'a'
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class BuiltinRansacker implements Ransacker
{
    /**
     * Database
     *
     * @var Database
     */
    protected $db;

    /**
     * Create ransacker of given databasae.
     *
     * @param Database $db
     */
    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * {@inheritDoc}
     */
    public static function of(Database $db) : Ransacker
    {
        return new static($db);
    }

    /**
     * {@inheritDoc}
     */
    public function convert($predicate, $value, ?\Closure $extention = null) : ?array
    {
        if (Utils::isBlank($value)) {
            return null;
        }
        if ($extention && $custom = $extention($this->db, $predicate, $value)) {
            return $custom;
        }
        if (is_int($predicate)) {
            $where  = [];
            $params = [];
            foreach ($value as $sub_conditions) {
                $sub_where  = [];
                $sub_params = [];
                foreach ($sub_conditions as $k => $v) {
                    [$expression, $v] = $this->convert($k, $v, $extention);
                    if ($expression) {
                        $sub_where[]    = $expression;
                        $sub_params[$k] = $v;
                    }
                }
                $where[]  = '('.implode(' AND ', $sub_where).')';
                $params[] = $sub_params;
            }
            return ['('.implode(' OR ', $where).')', $params];
        }
        if (is_array($value)) {
            return ["{$predicate} IN (:{$predicate})", $value];
        }
        if (Strings::endsWith($predicate, '_from')) {
            $column = substr($predicate, 0, -5);
            return ["{$column} >= :{$predicate}", $value];
        }
        if (Strings::endsWith($predicate, '_to')) {
            $column = substr($predicate, 0, -3);
            return ["{$column} <= :{$predicate}", $value];
        }
        if (!property_exists($this, $predicate)) {
            return [null, $value];
        }
        return ["{$predicate} = :{$predicate}", $value];
    }
}
