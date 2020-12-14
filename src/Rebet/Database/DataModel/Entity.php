<?php
namespace Rebet\Database\DataModel;

use Rebet\Database\Annotation\Defaults;
use Rebet\Database\Annotation\PhpType;
use Rebet\Database\Annotation\Table;
use Rebet\Database\Annotation\Unmap;
use Rebet\Database\Database;
use Rebet\Inflection\Inflector;
use Rebet\Tools\DateTime\DateTime;
use Rebet\Tools\Utility\Strings;
use ReflectionClass;

/**
 * Entity Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
abstract class Entity extends DataModel
{
    /**
     * Data create timestamp field name.
     * If you need change a field name then override constant value.
     * NOTE: If you override constant to null means `do not use timestamp auto fill`.
     *
     * @var string
     */
    const CREATED_AT = 'created_at';

    /**
     * Data update timestamp field name.
     * If you need change a field name then override constant value.
     * NOTE: If you override constant to null means `do not use timestamp auto fill`.
     *
     * @var string
     */
    const UPDATED_AT = 'updated_at';

    /**
     * Original data when fetched.
     *
     * @var static
     */
    protected $_origin = null;

    /**
     * Get and Set original data when fetched.
     *
     * @param Entity|null $origin (default: null)
     * @return self|null
     * @throws \InvalidArgumentException when the given $origin class is not same as this
     */
    public function origin(?Entity $origin = null) : ?self
    {
        if ($origin !== null && ($class = get_class($this)) !== get_class($origin)) {
            throw new \InvalidArgumentException("Origin must be same class of [{$class}].");
        }
        return $this->getset('_origin', $origin);
    }

    /**
     * Remove original data.
     *
     * @return self
     */
    public function removeOrigin() : self
    {
        $this->_origin = null;
        return $this;
    }

    /**
     * Get the table name (@Table annotated, Inflector::tableize or Inflector::pivotize) of this entity.
     *
     * @return string
     * @see Table
     * @see Inflector::tableize()
     * @see Inflector::pivotize()
     */
    public static function tabelName() : string
    {
        if ($table = static::meta(__METHOD__)) {
            return $table;
        }

        $ac = static::annotatedClass();
        if ($table = $ac->annotation(Table::class)) {
            return static::meta(__METHOD__, $table->value);
        }

        $short_name = $ac->reflector()->getShortName();
        return static::meta(__METHOD__, static::isPivot() ? Inflector::pivotize($short_name) : Inflector::tableize($short_name));
    }

    /**
     * It checks this entity is pivot table of many to many.
     * The condition of whether or not it is a pivot table is as follows.
     *
     * 1) The pivot table entity has two primary keys (ex: group_id and user_id)
     * 2) Expected table name will be joined name of two primary keys order by natural without '_id' suffix. (ex: group_user / NOT pluralize)
     * 3) The pivot table entity class name is Inflector::classify() of expected table name. (ex: GroupUser)
     *
     * @return boolean
     */
    protected static function isPivot() : bool
    {
        if ($is_pivot = static::meta(__METHOD__)) {
            return $is_pivot;
        }

        $primary_keys = static::primaryKeys();
        if (count($primary_keys) !== 2) {
            return static::meta(__METHOD__, false);
        }

        $expect_table_name = Inflector::pivotize(array_map(function ($key) { return Strings::rtrim($key, '_id'); }, $primary_keys));
        return static::meta(__METHOD__, (new ReflectionClass(static::class))->getShortName() === Inflector::classify($expect_table_name));
    }

    /**
     * Get unmaps (non public and @Unmap annotated) properties.
     *
     * @return array
     * @see Unmap
     */
    public static function unmaps() : array
    {
        if ($unmaps = static::meta(__METHOD__)) {
            return $unmaps;
        }

        $unmaps = [];
        $ac     = static::annotatedClass();
        foreach ($ac->properties() as $ap) {
            if (!$ap->reflector()->isPublic() || $ap->annotation(Unmap::class)) {
                $unmaps[] = $ap->reflector()->getName();
            }
        }
        return static::meta(__METHOD__, $unmaps);
    }

    /**
     * Get default (@Defaults with/without @PhpType annotated) properties.
     *
     * @return array [property_name => [default_value, null|php_type(from @PhpType)]]
     * @see Defaults
     * @see PhpType
     */
    public static function defaults() : array
    {
        if ($defaults = static::meta(__METHOD__)) {
            return $defaults;
        }

        $defaults = [];
        $ac       = static::annotatedClass();
        foreach ($ac->properties() as $ap) {
            $default = $ap->annotation(Defaults::class);
            if ($default) {
                $key            = $ap->reflector()->getName();
                $php_type       = $ap->annotation(PhpType::class);
                $defaults[$key] = [$default->value, $php_type ? $php_type->value : null];
            }
        }

        return static::meta(__METHOD__, $defaults);
    }

    /**
     * Get the property and value list that changed.
     * This method ignore unmaps (non public and @Unmaps annotated) properties and dynamic properties.
     *
     * @return array
     * @see Unmap
     * @see Entity::isDynamicProperty()
     */
    public function changes() : array
    {
        $changes = [];
        $unmaps  = static::unmaps();
        foreach ($this as $property => $value) {
            if (in_array($property, $unmaps) || $this->isDynamicProperty($property) || ($this->_origin === null && $value === null)) {
                continue;
            }
            if ($this->_origin === null || $value !== $this->_origin->$property) {
                $changes[$property] = $value;
            }
        }
        return $changes;
    }

    /**
     * It checks the entity was changed.
     * This method ignore unmaps (non public and @Unmaps annotated) properties and dynamic properties.
     *
     * @return bool
     */
    public function isDirty() : bool
    {
        $unmaps = static::unmaps();
        foreach ($this as $property => $value) {
            if (in_array($property, $unmaps) || $this->isDynamicProperty($property) || ($this->_origin === null && $value === null)) {
                continue;
            }
            if ($this->_origin === null || $value !== $this->_origin->$property) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check the given property is dynamic property or not.
     *
     * @param string $property
     * @return bool
     */
    public function isDynamicProperty(string $property) : bool
    {
        return !property_exists(static::class, $property);
    }

    /**
     * It check this entity is exists.
     *
     * @param Database|string|null $db (default: null)
     * @return boolean
     */
    public function exists($db = null) : bool
    {
        $condition = Database::buildPrimaryWheresFrom($this);
        return static::db($db)->exists("SELECT * FROM ".static::tabelName().$condition->where(), $condition->params);
    }

    /**
     * Create own data to given name database.
     * This method ignore unmaps (non public and @Unmaps annotated) properties and dynamic properties.
     *
     * @param DateTime|null $now (default: null)
     * @param Database|string|null $db (default: null)
     * @return bool
     */
    public function create(?DateTime $now = null, $db = null) : bool
    {
        return static::db($db)->create($this, $now);
    }

    /**
     * Update own changed data to given name database.
     * This method ignore unmaps (non public and @Unmaps annotated) properties and dynamic properties.
     *
     * @param DateTime|null $now (default: null)
     * @param Database|string|null $db (default: null)
     * @return bool
     */
    public function update(?DateTime $now = null, $db = null) : bool
    {
        return static::db($db)->update($this, $now);
    }

    /**
     * Save (Create/Update) own changed data to given name database.
     *
     * @param DateTime|null $now (default: null)
     * @param Database|string|null $db (default: null)
     * @return bool
     */
    public function save(?DateTime $now = null, $db = null) : bool
    {
        return static::db($db)->save($this, $now);
    }

    /**
     * Delete own changed data to given name database.
     *
     * @param Database|string|null $db (default: null)
     * @return bool
     */
    public function delete($db = null) : bool
    {
        return static::db($db)->delete($this);
    }

    /**
     * Update data using ransack conditions.
     *
     * @param array $changes
     * @param mixed $ransack conditions that arrayable (default: [])
     * @param DateTime|null $now (default: null)
     * @param Database|string|null $db (default: null)
     * @return int affected row count
     */
    public static function updateBy(array $changes, $ransack = [], ?DateTime $now = null, $db = null) : int
    {
        return static::db($db)->updateBy(static::class, $changes, $ransack, static::ransackAliases(), $now);
    }

    /**
     * Delete data using ransack conditions.
     *
     * @param mixed $ransack conditions that arrayable (default: [])
     * @param Database|string|null $db (default: null)
     * @return int affected row count
     */
    public static function deleteBy($ransack = [], $db = null) : int
    {
        return static::db($db)->deleteBy(static::class, $ransack, static::ransackAliases());
    }

    /**
     * It checks the data is exists using ransack conditions.
     *
     * @param mixed $ransack conditions that arrayable
     * @param Database|string|null $db (default: null)
     * @return bool
     */
    public static function existsBy($ransack, $db = null) : bool
    {
        return static::db($db)->existsBy(static::class, $ransack, static::ransackAliases());
    }

    /**
     * Count data using ransack conditions.
     *
     * @param mixed $ransack conditions that arrayable (default: [])
     * @param Database|string|null $db (default: null)
     * @return int
     */
    public static function count($ransack = [], $db = null) : int
    {
        return static::db($db)->countBy(static::class, $ransack, static::ransackAliases());
    }

    /**
     * {@inheritDoc}
     */
    protected static function buildSelectAllSql() : string
    {
        return "SELECT * FROM ".static::tabelName();
    }
}
