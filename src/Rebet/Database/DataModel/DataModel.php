<?php
namespace Rebet\Database\DataModel;

use Closure;
use Rebet\Annotation\AnnotatedClass;
use Rebet\Common\Arrays;
use Rebet\Common\Describable;
use Rebet\Common\Exception\LogicException;
use Rebet\Common\Getsetable;
use Rebet\Common\Json;
use Rebet\Common\Populatable;
use Rebet\Common\Reflector;
use Rebet\Database\Annotation\PrimaryKey;
use Rebet\Database\Condition;
use Rebet\Database\Dao;
use Rebet\Database\Database;
use Rebet\Database\Pagination\Pager;
use Rebet\Database\Pagination\Paginator;
use Rebet\Database\Ransack\Ransack;
use Rebet\Database\ResultSet;
use Rebet\Inflection\Inflector;

/**
 * Data Model Class
 *
 * @todo Implements belongsToMany
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
     * Eager loaded data cache
     *
     * @var array
     */
    protected $_eager_loads = [];

    /**
     * Relations entity cache
     *
     * @var array $_relations[table_name][relation_holder_object_hash][condition_digest] = $entity|$result_set
     */
    protected static $_relations = [];

    /**
     * It checks the given key eager loads data exists or not.
     *
     * @param string $key
     * @return boolean
     */
    protected function hasEagerLoads(string $key) : bool
    {
        return array_key_exists($key, $this->_eager_loads);
    }

    /**
     * Set given eager loads data.
     *
     * @param string $key
     * @param mixed $value
     * @return mixed that given value as it is.
     */
    protected function pushEagerLoads(string $key, $value)
    {
        return $this->_eager_loads[$key] = $value;
    }

    /**
     * Get a value from the eager loads, and remove it.
     *
     * @param string $key
     * @return mixed
     */
    protected function pullEagerLoads(string $key)
    {
        // @todo
        // return $this->_eager_loads[$key] ?? null;
        return Arrays::pull($this->_eager_loads, $key);
    }

    /**
     * Verify this class is super class of given $class.
     *
     * @param string|null $class name
     * @return void
     * @throws LogicException when give the class name that is not subclass of this class.
     */
    protected static function mustBeSuperclassOf(?string $class) : void
    {
        if ($class !== null && !is_subclass_of($class, static::class)) {
            throw LogicException::by("Invalid class name given. {$class} is not subclass of ".static::class.".");
        }
    }

    /**
     * Create 'sha256' hash string from given values.
     *
     * @param mixed ...$values
     * @return string
     */
    protected static function hash(...$values) : string
    {
        return Json::digest('sha256', $values);
    }

    /**
     * Generate primary hash of this data mode from primary key/values.
     *
     * @return string
     */
    public function primaryHash() : string
    {
        return static::hash(static::class, $this->primaryValues());
    }

    /**
     * Get primary values of this data model.
     *
     * @return array [primary_key => value, ... ]
     */
    public function primaryValues() : array
    {
        return $this->pluck(...static::primaryKeys());
    }

    /**
     * Generate foreign hash of this data mode from given foreign key/values.
     *
     * @param string $class name of data model
     * @param array $alias of ['local_key' => 'foreign_key'] if the column name is different (default: [])
     * @return string
     */
    public function foreignHash(string $class, array $alias = []) : string
    {
        return static::hash($class, $this->foreignValues($class, $alias));
    }

    /**
     * Get given foreign values of this data model.
     *
     * @param string $class name of data model
     * @param array $alias of ['local_key' => 'foreign_key'] if the column name is different (default: [])
     * @return array [foreign_key => value, ... ]
     */
    public function foreignValues(string $class, array $alias = []) : array
    {
        $foreigns = [];
        $alias    = array_flip($alias);
        foreach ($class::primaryKeys() as $foreign_key) {
            $foreigns[$foreign_key] = Reflector::get($this, $alias[$foreign_key] ?? $foreign_key);
        }
        return $foreigns;
    }

    /**
     * Pluck given column-value pairs.
     *
     * @param string ...$columns
     * @return array [column => value, ...]
     */
    public function pluck(string ...$columns) : array
    {
        $pluks = [];
        foreach ($columns as $column) {
            $pluks[$column] = $this->$column;
        }
        return $pluks;
    }

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
        [$sql, /*param*/] = static::buildSelectSql($db);
        $sql              = $db->appendWhereTo($sql, $where);

        return $db->find($sql, null, $params, $for_update, get_called_class());
    }

    /**
     * Select data model by given ransacks conditions.
     *
     * @param mixed $ransacks conditions that arrayable (default: [])
     * @param OrderBy|array|null $order_by (default: null)
     * @param int|null $limit (default: null)
     * @param bool $for_update (default: false)
     * @param Database|string|null $db (default: null)
     * @return ResultSet
     */
    public static function select($ransacks = [], $order_by = null, ?int $limit = null, bool $for_update = false, $db = null) : ResultSet
    {
        [$sql, $params] = static::buildSelectSql($db = static::db($db), Arrays::toArray($ransacks));
        return $db->select($sql, $order_by ?? static::defaultOrderBy(), $params, $limit, $for_update, get_called_class());
    }

    /**
     * Paginate data model by given ransacks conditions
     *
     * @param Pager $pager
     * @param mixed $ransacks conditions that arrayable (default: [])
     * @param OrderBy|array|null $order_by (default: null for get from defaultOrderBy())
     * @param bool $for_update (default: false)
     * @param Database|string|null $db (default: null)
     * @return Paginator
     */
    public static function paginate(Pager $pager, $ransacks = [], $order_by = null, bool $for_update = false, $db = null) : Paginator
    {
        [$sql, $params] = static::buildSelectSql($db = static::db($db), Arrays::toArray($ransacks));
        return $db->paginate($sql, $order_by ?? static::defaultOrderBy(), $pager, $params, $for_update, get_called_class(), static::buildOptimizedCountSql($db, $ransacks));
    }

    /**
     * Build data model select SQL using given ransack conditions.
     * If the ransack conditions is empty then select all of the data.
     *
     * @param Database $db
     * @param array $ransacks condition (default: [])
     * @return array ['sql', params]
     */
    protected static function buildSelectSql(Database $db, array $ransacks = []) : array
    {
        $where     = [];
        $params    = [];
        $alises    = static::ransackAliases();
        $extension = Closure::fromCallable([static::class, 'ransack']);
        foreach ($ransacks as $predicate => $value) {
            $condition = $db->ransacker()->resolve($predicate, $value, $alises, $extension);
            if ($condition) {
                $where[] = $condition->sql;
                $params  = array_merge($params, $condition->params);
            }
        }
        return [$db->appendWhereTo(static::buildSelectAllSql(), $where), $params];
    }

    /**
     * Build data model select all SQL.
     *
     * @return string of sql
     */
    abstract protected static function buildSelectAllSql() : string;

    /**
     * Build optimized count SQL using given ransack conditions for paginate.
     *
     * @param Database $db
     * @param array $ransacks conditions (default: [])
     * @return string|null
     */
    protected static function buildOptimizedCountSql(Database $db, array $ransacks = []) : ?string
    {
        return null;
    }

    /**
     * Convert extention ransack condition.
     * If you want to implement special ransack search conditions specific to the data model as shown below, please override this method.
     * NOTE: If this method return null then ransack handled by default behavior.
     *
     *   switch($ransack->origin()) {
     *     case 'tel':
     *       return $ransack->convert("REPLACE({col}, '-', '') = REPLACE({val}, '-', '')");
     *     case 'account_status':
     *       switch($ransack->value()) {
     *         case AccountStatus::ACTIVE(): return new Condition('U.resign_at IS NULL AND U.locked = 1');
     *         case AccountStatus::LOCKED(): return new Condition('U.resign_at IS NULL AND U.locked = 2');
     *         case AccountStatus::RESIGN(): return new Condition('U.resign_at IS NOT NULL'             );
     *       }
     *       return new Condition(null, null);
     *     case 'has_bank':
     *       return new Condition('EXISTS (SELECT * FROM bank AS B WHERE B.user_id = U.user_id)') ;
     *   }
     *   return parent::ransack($db, $ransack);
     *
     * @param Database $db
     * @param Ransack $ransack
     * @return Condition|null
     */
    protected static function ransack(Database $db, Ransack $ransack) : ?Condition
    {
        return null;
    }

    /**
     * Get ransack aliases of this data model.
     * If you want to define ransack aliases of this data model, please override this method.
     *
     * @return array
     */
    protected static function ransackAliases() : array
    {
        return [];
    }

    /**
     * Get default order by list for select and paginate.
     * This method return primary keys 'desc' order defaultly.
     * If you want to change default order then please override this method.
     *
     * @return array
     */
    protected static function defaultOrderBy() : array
    {
        return array_fill_keys(static::primaryKeys(), 'desc');
    }

    /**
     * Eager/Lazy load the 'belongs_to' relational data model.
     * You can define belongs to relation in sub class like below,
     *
     * - IN Article::class
     * public function user(bool $for_update = false) : User
     * {
     *     return $this->belongsTo(User:class, [], $for_update);
     * }
     *
     * And also you can control relationship by context like depend on login user role using Auth::user().
     *
     * NOTE:
     * This method does not cache the retrieved results.
     * Usually, this will not be a problem since the retrieved result is stored in a local variable or disposed for one-time use.
     * But if you don't want that, add caching function when overriding in subclasses.
     *
     * @param string $class of data model
     * @param array $alias of ['local_key' => 'foreign_key'] if the column name is different (default: [])
     * @param bool $for_update (default: false)
     * @param bool $eager_load (default: true)
     * @param string|null $name of relationship [used for key name of one-time storage for eager loads] (default: null for using caller function name)
     * @return mixed Class instance of given $class or null.
     */
    protected function belongsTo(string $class, array $alias = [], bool $for_update = false, bool $eager_load = true, ?string $name = null)
    {
        if (!$eager_load || $this->_belongs_result_set === null) {
            return $class::find($this->ransacksForBelongsTo($class, $alias), $for_update);
        }

        $cache_key = ($name ?? Reflector::caller()).'_'.Json::digest('sha256', $class, $alias);
        if ($this->hasEagerLoads($cache_key)) {
            return $this->pullEagerLoads($cache_key);
        }

        $ransacks = $this->eagerRansack(function (DataModel $dm) use ($class, $alias) { return $dm->ransacksForBelongsTo($class, $alias); });
        $rs       = Arrays::groupBy(
            $class::select($ransacks, [], null, $for_update)->toArray(),
            function ($v, $k) { return $v->primaryHash(); }
        );
        $eager_group = new ResultSet();
        foreach ($this->_belongs_result_set as $dm) {
            if ($belongs_to = $rs[$dm->foreignHash($class, $alias)][0] ?? null) {
                $belongs_to    = clone $belongs_to;
                $eager_group[] = $belongs_to;
            }
            $dm->pushEagerLoads($cache_key, $belongs_to);
        }

        return $this->pullEagerLoads($cache_key);
    }

    /**
     * Create optimized ransacks condition for eager loads.
     *
     * @param \Closure $extracter of each ransacks, function(DataModel $dm) :array { ... }
     * @return array of each ransack conditions
     */
    protected function eagerRansack(\Closure $extracter) : array
    {
        $this_class = get_class($this);
        $ransacks   = [];
        $duplicates = [];
        foreach ($this->_belongs_result_set as $dm) {
            if (!($dm instanceof $this_class)) {
                throw LogicException::by("Classes other than {$this_class} are mixed in the result set.");
            }
            $ransack = $extracter($dm);
            $digest  = Json::digest('sha256', $ransack);
            if (isset($duplicates[$digest])) {
                continue;
            }
            $duplicates[$digest] = true;
            if (count($ransack) === 1) {
                foreach ($ransack as $key => $value) {
                    $ransacks[$key][] = $value;
                }
            } else {
                $ransacks[0][] = $ransack;
            }
        }

        return $ransacks;
    }

    /**
     * Create `ransacks` relational conditions for 'belongs-to'.
     *
     * @param string $class
     * @param array $alias of ['local_key' => 'foreign_key'] if the column name is different (default: [])
     * @return array
     */
    protected function ransacksForBelongsTo(string $class, array $alias = []) : array
    {
        return $this->foreignValues($class, $alias);
    }

    /**
     * Eager/Lazy load the 'has_one' relational data model.
     * You can define has one relation in sub class like below,
     *
     * - IN User::class
     * public function bank(bool $for_update = false) : ?Bank
     * {
     *     return $this->hasOne(Bank:class, [], $for_update);
     * }
     *
     * public function parent(bool $for_update = false) : ?User
     * {
     *     return $this->hasOne(User:class, ['parent_id' => 'user_id'], $for_update);
     * }
     *
     * And also you can control relationship by context like depend on login user role using Auth::user().
     *
     * NOTE:
     * This method does not cache the retrieved results.
     * Usually, this will not be a problem since the retrieved result is stored in a local variable or disposed for one-time use.
     * But if you don't want that, add caching function when overriding in subclasses.
     *
     * @param string $class of relational data model
     * @param array $alias of ['primary_key' => 'other_key'] if the column name is different (default: [])
     * @param bool $for_update (default: false)
     * @param bool $eager_load (default: true)
     * @param string|null $name of relationship [used for key name of one-time storage for eager loads] (default: null for using caller function name)
     * @return mixed Class instance of given $class or null.
     */
    protected function hasOne(string $class, array $alias = [], bool $for_update = false, bool $eager_load = true, ?string $name = null)
    {
        if (!$eager_load || $this->_belongs_result_set === null) {
            return $class::find($this->ransacksForHas($alias), $for_update);
        }

        $cache_key = ($name ?? Reflector::caller()).'_'.Json::digest('sha256', $class, $alias);
        if ($this->hasEagerLoads($cache_key)) {
            return $this->pullEagerLoads($cache_key);
        }

        $ransacks = $this->eagerRansack(function (DataModel $dm) use ($alias) { return $dm->ransacksForHas($alias); });
        $rs       = Arrays::groupBy(
            $class::select($ransacks, [], null, $for_update)->toArray(),
            function ($v, $k) use ($alias) { return $v->foreignHash(static::class, $alias); }
        );
        $eager_group = new ResultSet();
        foreach ($this->_belongs_result_set as $dm) {
            if ($has_one = $rs[$dm->primaryHash()][0] ?? null) {
                $has_one       = clone $has_one;
                $eager_group[] = $has_one;
            }
            $dm->pushEagerLoads($cache_key, $has_one);
        }

        return $this->pullEagerLoads($cache_key);
    }

    /**
     * Create `ransacks` relational conditions for 'has-one/has-many'.
     *
     * @param array $alias of ['primary_key' => 'other_key'] if the column name is different (default: [])
     * @return array
     */
    protected function ransacksForHas(array $alias = []) : array
    {
        $conditions = [];
        foreach (static::primaryKeys() as $column) {
            $conditions[$alias[$column] ?? $column] = Reflector::get($this, $column) ;
        }
        return $conditions;
    }

    /**
     * Eager/Lazy load the 'has_many' relational data models.
     * You can define has many relation in sub class like below,
     *
     * - IN User::class
     * public function articles($ransack = [], ?int $limit = null, bool $for_update = false) : array
     * {
     *     return $this->hasMany(Article:class, [], $ransack, ['created_at' => 'DESC'], $limit, $for_update);
     * }
     *
     * public function publichedArticles(?int $limit = null, bool $for_update = false) : array
     * {
     *     return $this->hasMany(
     *         Article:class,
     *         [],
     *         [
     *             'publiched_at_not_null' => 1,
     *             'publiched_at_lteq'     => DateTime::now(),
     *             'publiched_status'      => PublichedStatus::OPEN(),
     *         ],
     *         ['publiched_at' => 'DESC', 'article_id' => 'DESC'],
     *         $limit,
     *         $for_update,
     *     );
     * }
     *
     * And also you can control relationship by context like depend on login user role using Auth::user().
     *
     * NOTE:
     * This method does not cache the retrieved results.
     * Usually, this will not be a problem since the retrieved result is stored in a local variable or disposed for one-time use.
     * But if you don't want that, add caching function when overriding in subclasses.
     *
     * @param string $class
     * @param array $alias of ['primary_key' => 'other_key'] if the column name is different (default: [])
     * @param array $ransacks of preconditions (default: [])
     * @param OrderBy|array|null $order_by (default: null for get from defaultOrderBy())
     * @param integer|null $limit (default: null)
     * @param bool $for_update (default: false)
     * @param boolean $eager_load (default: true)
     * @param string|null $name of relationship [used for key name of one-time storage for eager loads] (default: null for using caller function name)
     * @return array
     */
    protected function hasMany(string $class, array $alias = [], array $ransacks = [], $order_by = null, ?int $limit = null, bool $for_update = false, bool $eager_load = true, ?string $name = null) : array
    {
        if (!$eager_load || $this->_belongs_result_set === null) {
            return $class::select(array_merge($ransacks, $this->ransacksForHas($alias)), $order_by, $limit, $for_update)->toArray();
        }

        $cache_key = ($name ?? Reflector::caller()).'_'.Json::digest('sha256', $class, $alias, $ransacks, $order_by, $limit);
        if ($this->hasEagerLoads($cache_key)) {
            return $this->pullEagerLoads($cache_key);
        }

        $ransacks = array_merge($ransacks, $this->eagerRansack(function (DataModel $dm) use ($alias) { return $dm->ransacksForHas($alias); }));
        $rs       = Arrays::groupBy(
            $class::select($ransacks, $order_by, null, $for_update)->toArray(),
            function ($v, $k) use ($alias) { return $v->foreignHash(static::class, $alias); }
        );
        $eager_group = new ResultSet();
        foreach ($this->_belongs_result_set as $dm) {
            if ($has_many = array_slice($rs[$dm->primaryHash()] ?? [], 0, $limit)) {
                foreach ($has_many as $value) {
                    $eager_group[] = $value;
                }
            }
            $dm->pushEagerLoads($cache_key, $has_many);
        }

        return $this->pullEagerLoads($cache_key);
    }
}
