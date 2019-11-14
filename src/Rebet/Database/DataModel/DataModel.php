<?php
namespace Rebet\Database\DataModel;

use Rebet\Annotation\AnnotatedClass;
use Rebet\Common\Arrays;
use Rebet\Common\Describable;
use Rebet\Common\Exception\LogicException;
use Rebet\Common\Getsetable;
use Rebet\Common\Populatable;
use Rebet\Common\Reflector;
use Rebet\Common\Utils;
use Rebet\Database\Annotation\PrimaryKey;
use Rebet\Database\Dao;
use Rebet\Database\Database;
use Rebet\Database\Pagination\Pager;
use Rebet\Database\Pagination\Paginator;
use Rebet\Database\ResultSet;
use Rebet\Inflection\Inflector;

/**
 * Data Model Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
abstract class DataModel
{
    use Populatable, Describable, Getsetable;

    /**
     * @var AnnotatedClass[]
     */
    protected static $_annotated_class = [];

    /**
     * Meta information cache
     *
     * @var array
     */
    protected static $_meta = [];

    /**
     * Result set that this data model belongs to.
     *
     * @var ResultSet|null
     */
    protected $_belongs_result_set = null;

    /**
     * Relations data model
     *
     * @var array
     */
    protected $_relations = [];

    /**
     * Get and Set result set container of this data model
     *
     * @return self|null
     */
    public function belongsResultSet(?ResultSet $rs = null)
    {
        return $this->getset('_belongs_result_set', $rs);
    }

    /**
     * Create an Data Model object
     */
    public function __construct()
    {
        $class = get_class($this);
        if (!isset(static::$_annotated_class[$class])) {
            static::$_annotated_class[$class] = new AnnotatedClass($class);
        }
    }

    /**
     * Get and Set meta data.
     *
     * @param string $name
     * @param mixed $value
     * @return mixed
     */
    protected static function meta(string $name, $value = null)
    {
        $class = get_called_class();
        if ($value === null) {
            return static::$_meta[$class][$name] ?? null ;
        }

        static::$_meta[$class][$name] = $value;
        return $value;
    }

    /**
     * Get the annotated class.
     *
     * @return AnnotatedClass
     */
    protected static function annotatedClass() : AnnotatedClass
    {
        $class = get_called_class();
        if (isset(static::$_annotated_class[$class])) {
            return static::$_annotated_class[$class];
        }
        return static::$_annotated_class[$class] = new AnnotatedClass($class);
    }

    /**
     * Get primary keys properties.
     *
     * @return array
     */
    public static function primaryKeys() : array
    {
        if ($primary_keys = static::meta(__METHOD__)) {
            return $primary_keys;
        }

        $primary_keys = [];
        $ac           = static::annotatedClass();
        foreach ($ac->properties() as $ap) {
            if ($ap->annotation(PrimaryKey::class)) {
                $primary_keys[] = $ap->reflector()->getName();
            }
        }

        if (empty($primary_keys)) {
            $pkey = Inflector::primarize($ac->reflector()->getShortName());
            if ($ac->reflector()->hasProperty($pkey)) {
                $primary_keys[] = $pkey;
            }
        }

        return static::meta(__METHOD__, $primary_keys);
    }

    /**
     * Get current focused database.
     * If the other database name will be given then return other database but keep current focused database.
     *
     * @param Database|string|null $db name if you want to access when just once (default: null)
     * @return Database
     */
    protected static function db($db = null) : Database
    {
        $db = $db ?? Dao::current() ?? Dao::db() ;
        return $db instanceof Database ? $db : Dao::db($db, false) ;
    }

    /**
     * Convert the type from other to self.
     * If conversion is not possible then return null.
     *
     * @param mixed $primaries primary key value or array|object of primary keys
     * @return self|null
     */
    public static function valueOf($primaries) : ?self
    {
        return static::find($primaries);
    }

    /**
     * Find data model by given primaries
     *
     * @param mixed $primaries primary key value or array|object of primary keys
     * @param bool $for_update (default: false)
     * @param Database|string|null $db (default: null)
     * @return self|null
     */
    public static function find($primaries, bool $for_update = false, $db = null) : ?self
    {
        $where            = [];
        $params           = [];
        $primary_keys     = static::primaryKeys();
        $is_composite_key = count($primary_keys) !== 1 ;

        foreach ($primary_keys as $column) {
            $where[]         = "{$column} = :{$column}";
            $params[$column] = $is_composite_key ? Reflector::get($primaries, $column) : $primaries ;
        }

        $db               = static::db($db);
        [$sql, /*param*/] = static::buildSelectSql();
        $analyzer         = $db->compiler()->analyzer($db, $sql);
        $condition_sql    = implode(' AND ', $where);
        if ($analyzer->hasGroupBy()) {
            $sql = $analyzer->hasHaving() ? "{$sql} AND ({$condition_sql})" : "{$sql} HAVING {$condition_sql}" ;
        } else {
            $sql = $analyzer->hasWhere() || $analyzer->hasHaving() ? "{$sql} AND ({$condition_sql})" : "{$sql} WHERE {$condition_sql}" ;
        }

        return $db->find($sql, null, $params, $for_update, get_called_class());
    }

    /**
     * Select data model by given conditions
     *
     * @param mixed $conditions (default: [])
     * @param OrderBy|array|null $order_by (default: null)
     * @param int|null $limit (default: null)
     * @param bool $for_update (default: false)
     * @param Database|string|null $db (default: null)
     * @return ResultSet
     */
    public static function select($conditions = [], $order_by = null, ?int $limit = null, bool $for_update = false, $db = null) : ResultSet
    {
        $order_by       = $order_by ?? static::defaultOrderBy();
        $acceptables    = static::acceptableConditions();
        $conditions     = Arrays::toArray($conditions);
        $conditions     = empty($acceptables) ? $conditions : Arrays::only($conditions, $acceptables);
        [$sql, $params] = static::buildSelectSql($conditions);
        return static::db($db)->select($sql, $order_by, $params, $limit, $for_update, get_called_class());
    }

    /**
     * Paginate data model by given conditions
     *
     * @param Pager $pager
     * @param mixed $conditions (default: [])
     * @param OrderBy|array|null $order_by (default: null for get from defaultOrderBy())
     * @param bool $for_update (default: false)
     * @param Database|string|null $db (default: null)
     * @return Paginator
     */
    public static function paginate(Pager $pager, $conditions = [], $order_by = null, bool $for_update = false, $db = null) : Paginator
    {
        $order_by       = $order_by ?? static::defaultOrderBy();
        $acceptables    = static::acceptableConditions();
        $conditions     = Arrays::toArray($conditions);
        $conditions     = empty($acceptables) ? $conditions : Arrays::only($conditions, $acceptables);
        [$sql, $params] = static::buildSelectSql($conditions);
        return static::db($db)->paginate($sql, $order_by, $pager, $params, $for_update, get_called_class(), static::buildOptimizedCountSql($conditions));
    }

    /**
     * Build data model select SQL using given conditions.
     * If the conditions is empty then select all of the data.
     *
     * @param array $conditions (default: [])
     * @return array ['sql', params]
     */
    protected static function buildSelectSql(array $conditions = []) : array
    {
        $where  = [];
        $params = [];
        foreach ($conditions as $key => $value) {
            [$expression, $value] = static::buildConditionalExpression($key, $value);
            if ($expression) {
                $where[]      = $expression;
                $params[$key] = $value;
            }
        }
        return [static::buildSelectAllSql().(empty($where) ? '' : ' WHERE '.implode(' AND ', $where)), $params];
    }

    /**
     * Build data model select all SQL.
     *
     * @return string of sql
     */
    abstract protected static function buildSelectAllSql() : string;

    /**
     * Build optimized count SQL using given conditions for paginate.
     *
     * @param array $conditions (default: [])
     * @return string|null
     */
    protected static function buildOptimizedCountSql(array $conditions = []) : ?string
    {
        return null;
    }

    /**
     * Build 'WHERE' condition expression part from given condition key and value.
     * Default condition rule like below,
     *
     * ransack
     *
     *  1) If $value is null then ignored
     *     ['name' => null] => ''
     *  2) If $key is integer then join sub conditions by 'OR'.
     *     [['name' => 'a', 'gender' => 1], ['name' => 'b', 'gender' => 2]] => ((name = 'a' AND gender = 1) OR (name = 'b' AND gender = 2))
     *  3) If $value is array then in condition
     *     ['name' => ['a', 'b']] => name IN ('a', 'b')
     *  4) If $key ends with '_not_in' then not in condition
     *     ['name_not_in' => ['a', 'b']] => name NOT IN ('a', 'b')
     *  4) If $key ends with '_grator_than' then grator than condition
     *     ['birthday_grator_than' => '1980-01-01'] => birthday > '1980-01-01'
     *  5) If $key ends with '_grator_than_equals' then grator than equals condition
     *     ['birthday_grator_than_equals' => '1980-01-01'] => birthday >= '1980-01-01'
     *  6) If $key ends with '_less_than' then less than condition
     *     ['birthday_less_than' => '1980-01-01'] => birthday < '1980-01-01'
     *  7) If $key ends with '_less_than_equals' then less than equals condition
     *     ['birthday_less_than_equals' => '1980-01-01'] => birthday <= '1980-01-01'
     *  8) If $key ends with '_not_equals' then not equals condition
     *     ['birthday_not_equals' => '1980-01-01'] => birthday <> '1980-01-01'
     *  9) If $key ends with '_from' then grator than equal condition
     *     ['birthday_from' => '1980-01-01'] => birthday >= '1980-01-01'
     * 10) If $key ends with '_to' then less than equal condition
     *     ['birthday_to' => '1980-01-01'] => birthday <= '1980-01-01'
     * 11) If $key ends with '_contains' then like '%?%' condition
     *     ['name_contains' => '100%'] => name LIKE '%100|%%' ESCAPE '|'
     * 12) If $key ends with '_not_contains' then not like '%?%' condition
     *     ['name_not_contains' => '100%'] => name LIKE NOT '%100|%%' ESCAPE '|'
     * 13) If $key ends with '_starts_with' then like '?%' condition
     *     ['name_starts_with' => '100%'] => name LIKE '100|%%' ESCAPE '|'
     * 14) If $key ends with '_not_starts_with' then like '?%' condition
     *     ['name_not_starts_with' => '100%'] => name NOT LIKE '100|%%' ESCAPE '|'
     * 15) If $key ends with '_ends_with' then like '%?' condition
     *     ['name_ends_with' => '100%'] => name LIKE '%100|%' ESCAPE '|'
     * 16) If $key ends with '_not_ends_with' then like '%?' condition
     *     ['name_not_ends_with' => '100%'] => name NOT LIKE '%100|%' ESCAPE '|'
     * 17) Otherwise then equals condition
     *     ['name' => 'foo'] => name = 'foo'
     *
     * Please override this method to specify special conditions.
     *
     * ex)
     *   if(Utils::isBlank($value)) { return [null, $value]; }
     *   if(Strings::endsWith($key, '_match')) {
     *     $column = substr($key, 0, -6);
     *     return ["{$table_alias}{$key} REGEXP :{$key}", $value];
     *   }
     *   switch($key) {
     *     case 'name':
     *       return ['(U.name LIKE :name || U.name_ruby LIKE :name)', '%'.addcslashes($value, '\_%').'%']
     *     case 'account_status':
     *       switch($value) {
     *         case AccountStatus::ACTIVE(): return ['U.resign_at IS NULL AND U.locked = 1', $value];
     *         case AccountStatus::LOCKED(): return ['U.resign_at IS NULL AND U.locked = 2', $value];
     *         case AccountStatus::RESIGN(): return ['U.resign_at IS NOT NULL'             , $value];
     *       }
     *       return [null, $value];
     *     case 'has_bank':
     *       return ['EXISTS (SELECT * FROM bank AS B WHERE B.user_id = U.user_id)', $value] ;
     *   }
     *   return parent::buildConditionalExpression($key, $value, 'U');
     *
     * @param int|string $key
     * @param mixed $value
     * @param string|null $table_alias (default: null)
     * @return array ['condition explession', converted value]
     */
    protected static function buildConditionalExpression($key, $value, ?string $table_alias = null) : array
    {
        if (Utils::isBlank($value)) {
            return [null, $value];
        }
        $table_alias = $table_alias ? "{$table_alias}." : '' ;
        if (is_int($key)) {
            $where  = [];
            $params = [];
            foreach ($value as $sub_conditions) {
                $sub_where  = [];
                $sub_params = [];
                foreach ($sub_conditions as $k => $v) {
                    [$expression, $v] = static::buildConditionalExpression($k, $v, $table_alias);
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
            return ["{$table_alias}{$key} IN (:{$key})", $value];
        }
        if (Strings::endsWith($key, '_from')) {
            $column = substr($key, 0, -5);
            return ["{$table_alias}{$column} >= :{$key}", $value];
        }
        if (Strings::endsWith($key, '_to')) {
            $column = substr($key, 0, -3);
            return ["{$table_alias}{$column} <= :{$key}", $value];
        }
        if (!property_exists($this, $key)) {
            return [null, $value];
        }
        return ["{$table_alias}{$key} = :{$key}", $value];
    }

    /**
     * Get acceptable search condition key list for select and paginate.
     * If this list is empty then accept all.
     * If you want to limit the search conditions, please override with subclass.
     *
     * @return array
     */
    protected static function acceptableConditions() : array
    {
        return [];
    }

    /**
     * Get default order by list for select and paginate.
     * This method return no order defaultly.
     * If you want to change default order then please override this method.
     *
     * @return array
     */
    protected static function defaultOrderBy() : array
    {
        return [];
    }

    /**
     * Get relationship configure of this data model like 'has_one', 'has_many', 'belongs_to' and 'belongs_to_many'.
     * Configure settings like below,
     *
     *  return [
     *    'methodName' => ['relation_type', SubClassOfDataModel::class, alias map, ransack, order by, limit],
     *
     *    // exsamples like below
     *    'bank'                    => ['has_one'   , Bank::class   ],
     *    'articles'                => ['has_meny'  , Article::class],
     *    'owner'                   => ['belongs_to', User::class   , ['owner_id' => 'user_id']],
     *    'latestPublishedArticles' => ['has_meny'  , Article::class, [], ['available' => true, 'open_at_less_than' => DateTime::now()], ['open_at' => 'desc'], 5],
     *
     *    // and also you can control relationship by context like depend on login user role.
     *    'articles'                => ['has_meny'  , Article::class, [], Auth::user()->is('admin') ? [] : ['available' => true]],
     *  ]
     *
     * And call $data_model->methodName(bool $for_update = false, bool $eager_load = true) like $user->bank(), $article->owner(true).
     * If relation_type is has_one or belongs_to then return configured SubClassOfDataModel::class, otherwise return ResultSet that contains configured SubClassOfDataModel::class.
     *
     * @return array
     */
    protected static function relations() : array
    {
        return [];
    }

    /**
     * Lazy/Eager load the 'belongs-to' relational data model.
     * It can be used by implementing a method that resolves the relation in a subclass.
     *
     * ex) in Phone Class
     *   public function user() : ?User { return $this->belongsTo(User::class, 'user'); }
     *   public function owner() : ?User { return $this->belongsTo(User::class, 'owner', ['owner_id' => 'user_id']); }
     *   public function user(bool $for_update = false) : ?User { return $this->belongsTo(User::class, 'user', [], $for_update); }
     *
     * @param string $class of data model
     * @param string $name for relational data model cache. this name must be unique in the data model.
     * @param array $alias of ['local_key' => 'foreign_key'] if the column name is different (default: [])
     * @param bool $for_update (default: false)
     * @param bool $eager_load (default: true)
     * @return mixed Class instance of given $class or null.
     */
    protected function belongsTo(string $class, string $name, array $alias = [], bool $for_update = false, bool $eager_load = true)
    {
        if (array_key_exists($name, $this->_relations)) {
            return $this->_relations[$name];
        }

        if ($eager_load && $this->_belongs_result_set) {
            $this_class     = get_class($this);
            $sub_conditions = [];
            foreach ($this->_belongs_result_set as $i => $dm) {
                if (!($dm instanceof $this_class)) {
                    throw LogicException::by("Classes other than {$this_class} are mixed in the result set.");
                }
                $sub_conditions[$i] = $dm->relationalConditionsForBelongsTo($class, $alias);
            }

            $primary_keys = $class::primaryKeys();
            if (count($primary_keys) === 1) {
                $conditions[$primary_keys[0]] = array_unique(Arrays::pluck($sub_conditions, $primary_keys[0]), SORT_REGULAR);
            } else {
                $conditions[] = $sub_conditions;
            }

            $rs = Arrays::groupBy($class::select($conditions, null, null, $for_update)->toArray(), $primary_keys);
            foreach ($this->_belongs_result_set as $i => $dm) {
                $item = $rs;
                foreach ($sub_conditions[$i] as $value) {
                    $item = $item[$value] ?? null;
                    if ($item === null) {
                        break;
                    }
                }
                $dm->_relations[$name] = $item;
            }

            return $this->_relations[$name];
        }

        return $this->_relations[$name] = $class::find($this->relationalConditionsForBelongsTo($class, $alias), $for_update);
    }

    /**
     * Create relational conditions for 'belongs-to'.
     *
     * @param string $class
     * @param array $alias of ['local_key' => 'foreign_key'] if the column name is different (default: [])
     * @return array
     */
    protected function relationalConditionsForBelongsTo(string $class, array $alias = []) : array
    {
        $conditions = [];
        $alias      = array_flip($alias);
        foreach ($class::primaryKeys() as $column) {
            $conditions[$column] = Reflector::get($this, $alias[$column] ?? $column) ;
        }
        return $conditions;
    }

    /**
     * Lazy/Eager load the 'has-one' relational data model.
     * It can be used by implementing a method that resolves the relation in a subclass.
     *
     * ex) in User Class
     *   public function phone() : ?Phone { return $this->hasOne(Phone::class, 'phone'); }
     *   public function phone() : ?Phone { return $this->hasOne(Phone::class, 'phone', ['user_id' => 'owner_id']); }
     *
     * @param string $class of relational data model
     * @param string $name for relational data model cache. this name must be unique in the data model.
     * @param array $alias of ['primary_key' => 'other_key'] if the column name is different (default: [])
     * @param bool $for_update (default: false)
     * @param bool $eager_load (default: true)
     * @return mixed Class instance of given $class or null.
     */
    protected function hasOne(string $class, string $name, array $alias = [], bool $for_update = false, bool $eager_load = true)
    {
        if (array_key_exists($name, $this->_relations)) {
            return $this->_relations[$name];
        }

        if ($eager_load && $this->_belongs_result_set) {
            $this_class     = get_class($this);
            $sub_conditions = [];
            foreach ($this->_belongs_result_set as $i => $dm) {
                if (!($dm instanceof $this_class)) {
                    throw LogicException::by("Classes other than {$this_class} are mixed in the result set.");
                }
                $sub_conditions[$i] = $dm->relationalConditionsForHas($alias);
            }

            $primary_keys = static::primaryKeys();
            if (count($primary_keys) === 1) {
                $conditions[$primary_keys[0]] = array_unique(Arrays::pluck($sub_conditions, $primary_keys[0]), SORT_REGULAR);
            } else {
                $conditions[] = $sub_conditions;
            }

            $rs = Arrays::groupBy($class::select($conditions, null, null, $for_update)->toArray(), $primary_keys);
            foreach ($this->_belongs_result_set as $i => $dm) {
                $item = $rs;
                foreach ($sub_conditions[$i] as $value) {
                    $item = $item[$value] ?? null;
                    if ($item === null) {
                        break;
                    }
                }
                $dm->_relations[$name] = $item;
            }

            return $this->_relations[$name];
        }

        return $this->_relations[$name] = $class::find($this->relationalConditionsForHas($alias), $for_update);
    }

    /**
     * Create relational conditions for 'has-one/has-many'.
     *
     * @param array $alias of ['primary_key' => 'other_key'] if the column name is different (default: [])
     * @return array
     */
    protected function relationalConditionsForHas(array $alias = []) : array
    {
        $conditions = [];
        foreach (static::primaryKeys() as $column) {
            $conditions[$alias[$column] ?? $column] = Reflector::get($this, $column) ;
        }
        return $conditions;
    }

    /**
     * Lazy/Eager load the 'has-many' relational data models.
     * It can be used by implementing a method that resolves the relation in a subclass.
     *
     * ex) in User Class
     *   public function phones() : ResultSet { return $this->hasMany(Phone::class, 'phones'); }
     *   public function phones() : ResultSet { return $this->hasMany(Phone::class, 'phones', ['user_id' => 'owner_id']); }
     *
     * @param string $class
     * @param string $name for relational data models cache. this name must be unique in the data model.
     * @param array $alias of ['primary_key' => 'other_key'] if the column name is different (default: [])
     * @param array $conditions (default: [])
     * @param OrderBy|array|null $order_by (default: null for get from defaultOrderBy())
     * @param integer|null $limit (default: null)
     * @param bool $for_update (default: false)
     * @param boolean $eager_load (default: true)
     * @return ResultSet
     */
    protected function hasMany(string $class, string $name, array $alias = [], array $conditions = [], $order_by = null, ?int $limit = null, bool $for_update = false, bool $eager_load = true) : ResultSet
    {
        if (array_key_exists($name, $this->_relations)) {
            return $this->_relations[$name];
        }

        if ($eager_load && $this->_belongs_result_set) {
            $this_class     = get_class($this);
            $sub_conditions = [];
            foreach ($this->_belongs_result_set as $i => $dm) {
                if (!($dm instanceof $this_class)) {
                    throw LogicException::by("Classes other than {$this_class} are mixed in the result set.");
                }
                $sub_conditions[$i] = $dm->relationalConditionsForHas($alias);
            }

            $primary_keys = static::primaryKeys();
            if (count($primary_keys) === 1) {
                $conditions[$primary_keys[0]] = array_unique(Arrays::pluck($sub_conditions, $primary_keys[0]), SORT_REGULAR);
            } else {
                $conditions[] = $sub_conditions;
            }

            $rs = Arrays::groupBy($class::select($conditions, $order_by, null, $for_update)->toArray(), $primary_keys);
            foreach ($this->_belongs_result_set as $i => $dm) {
                $item = $rs;
                foreach ($sub_conditions[$i] as $value) {
                    $item = $item[$value] ?? null;
                    if ($item === null) {
                        break;
                    }
                }
                $dm->_relations[$name] = $limit && $item ? array_slice($item, 0, $limit) : $item ;
            }

            return $this->_relations[$name];
        }

        return $this->_relations[$name] = $class::select(array_merge($conditions, $this->relationalConditionsForHas($alias)), $order_by, $limit, $for_update);
    }

    /**
     * Reset relations data model cache.
     *
     * @param string|null $name (default: null for all reset)
     * @return self
     */
    public function resetRelations(?string $name = null) : self
    {
        if ($name) {
            unset($this->_relations[$name]);
        } else {
            $this->_relations = [];
        }
        return $this;
    }
}
