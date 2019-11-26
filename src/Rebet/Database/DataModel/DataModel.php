<?php
namespace Rebet\Database\DataModel;

use Closure;
use Rebet\Annotation\AnnotatedClass;
use Rebet\Common\Arrays;
use Rebet\Common\Describable;
use Rebet\Common\Exception\LogicException;
use Rebet\Common\Getsetable;
use Rebet\Common\Populatable;
use Rebet\Common\Reflector;
use Rebet\Database\Annotation\PrimaryKey;
use Rebet\Database\Dao;
use Rebet\Database\Database;
use Rebet\Database\Pagination\Pager;
use Rebet\Database\Pagination\Paginator;
use Rebet\Database\Ransack\Predicate;
use Rebet\Database\Ransack\Ransack;
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
            [$expression, $new_value] = $db->ransacker()->convert($predicate, $value, $alises, $extension) ?? [null, []];
            if ($expression) {
                $where[] = $expression;
                $params  = array_merge($params, $new_value);
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
     *   switch($predicate) {
     *     case 'name':
     *       return $db->ransacker()->convert('name_or_name_ruby_contains_fuzzy', $value);
     *       return ['(U.name collate utf8_unicode_ci LIKE :name || U.name_ruby collate utf8_unicode_ci LIKE :name)', '%'.addcslashes($value, '\_%').'%']
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
     *   return parent::convertCustomRansack($db, $predicate, $value);
     *
     * @param Database $db
     * @param Ransack $ransack
     * @return array|null
     */
    protected static function ransack(Database $db, Ransack $ransack) : ?array
    {
        return null;
    }

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
     * {@inheritDoc}
     */
    public function __call(string $name, array $args)
    {
        $relation = static::relations()[$name] ?? null;
        if ($relation) {
            [$type, $class, $alias, $ransacks, $order_by, $limit] = $relation;
            switch ($type) {
                case 'has_one':    return $this->hasOne($class, $name, $alias, ...$args);
                case 'has_meny':   return $this->hasMany($class, $name, $alias, $ransacks, $order_by, $limit, ...$args);
                case 'belongs_to': return $this->belongsTo($class, $name, $alias, ...$args);
            }
        }

        return parent::__call($name, $args);
    }

    /**
     * Eager/Lazy load the 'belongs_to' relational data model.
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
            $this_class   = get_class($this);
            $ransacks     = [];
            $sub_ransacks = [];
            foreach ($this->_belongs_result_set as $i => $dm) {
                if (!($dm instanceof $this_class)) {
                    throw LogicException::by("Classes other than {$this_class} are mixed in the result set.");
                }
                $sub_ransacks[$i] = $dm->ransacksForBelongsTo($class, $alias);
            }

            $primary_keys = $class::primaryKeys();
            if (count($primary_keys) === 1) {
                $ransacks[$primary_keys[0]] = array_unique(Arrays::pluck($sub_ransacks, $primary_keys[0]), SORT_REGULAR);
            } else {
                $ransacks[] = $sub_ransacks;
            }

            $rs = Arrays::groupBy($class::select($ransacks, null, null, $for_update)->toArray(), $primary_keys);
            foreach ($this->_belongs_result_set as $i => $dm) {
                $item = $rs;
                foreach ($sub_ransacks[$i] as $value) {
                    $item = $item[$value] ?? null;
                    if ($item === null) {
                        break;
                    }
                }
                $dm->_relations[$name] = $item;
            }

            return $this->_relations[$name];
        }

        return $this->_relations[$name] = $class::find($this->ransacksForBelongsTo($class, $alias), $for_update);
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
        $conditions = [];
        $alias      = array_flip($alias);
        foreach ($class::primaryKeys() as $column) {
            $conditions[$column] = Reflector::get($this, $alias[$column] ?? $column) ;
        }
        return $conditions;
    }

    /**
     * Eager/Lazy load the 'has_one' relational data model.
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
            $ransacks       = [];
            $sub_ransacks   = [];
            foreach ($this->_belongs_result_set as $i => $dm) {
                if (!($dm instanceof $this_class)) {
                    throw LogicException::by("Classes other than {$this_class} are mixed in the result set.");
                }
                $sub_ransacks[$i] = $dm->ransacksForHas($alias);
            }

            $primary_keys = static::primaryKeys();
            if (count($primary_keys) === 1) {
                $ransacks[$primary_keys[0]] = array_unique(Arrays::pluck($sub_ransacks, $primary_keys[0]), SORT_REGULAR);
            } else {
                $ransacks[] = $sub_ransacks;
            }

            $rs = Arrays::groupBy($class::select($ransacks, null, null, $for_update)->toArray(), $primary_keys);
            foreach ($this->_belongs_result_set as $i => $dm) {
                $item = $rs;
                foreach ($sub_ransacks[$i] as $value) {
                    $item = $item[$value] ?? null;
                    if ($item === null) {
                        break;
                    }
                }
                $dm->_relations[$name] = $item;
            }

            return $this->_relations[$name];
        }

        return $this->_relations[$name] = $class::find($this->ransacksForHas($alias), $for_update);
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
     *
     * @param string $class
     * @param string $name for relational data models cache. this name must be unique in the data model.
     * @param array $alias of ['primary_key' => 'other_key'] if the column name is different (default: [])
     * @param array $ransacks of preconditions (default: [])
     * @param OrderBy|array|null $order_by (default: null for get from defaultOrderBy())
     * @param integer|null $limit (default: null)
     * @param bool $for_update (default: false)
     * @param boolean $eager_load (default: true)
     * @return ResultSet
     */
    protected function hasMany(string $class, string $name, array $alias = [], array $ransacks = [], $order_by = null, ?int $limit = null, bool $for_update = false, bool $eager_load = true) : ResultSet
    {
        if (array_key_exists($name, $this->_relations)) {
            return $this->_relations[$name];
        }

        if ($eager_load && $this->_belongs_result_set) {
            $this_class     = get_class($this);
            $sub_ransacks   = [];
            foreach ($this->_belongs_result_set as $i => $dm) {
                if (!($dm instanceof $this_class)) {
                    throw LogicException::by("Classes other than {$this_class} are mixed in the result set.");
                }
                $sub_ransacks[$i] = $dm->ransacksForHas($alias);
            }

            $primary_keys = static::primaryKeys();
            if (count($primary_keys) === 1) {
                $ransacks[$primary_keys[0]] = array_unique(Arrays::pluck($sub_ransacks, $primary_keys[0]), SORT_REGULAR);
            } else {
                $ransacks[] = $sub_ransacks;
            }

            $rs = Arrays::groupBy($class::select($ransacks, $order_by, null, $for_update)->toArray(), $primary_keys);
            foreach ($this->_belongs_result_set as $i => $dm) {
                $item = $rs;
                foreach ($sub_ransacks[$i] as $value) {
                    $item = $item[$value] ?? null;
                    if ($item === null) {
                        break;
                    }
                }
                $dm->_relations[$name] = $limit && $item ? array_slice($item, 0, $limit) : $item ;
            }

            return $this->_relations[$name];
        }

        return $this->_relations[$name] = $class::select(array_merge($ransacks, $this->ransacksForHas($alias)), $order_by, $limit, $for_update);
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
