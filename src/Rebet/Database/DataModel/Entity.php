<?php
namespace Rebet\Database\DataModel;

use Rebet\Common\Reflector;
use Rebet\Database\Annotation\Defaults;
use Rebet\Database\Annotation\PhpType;
use Rebet\Database\Annotation\Table;
use Rebet\Database\Annotation\Unmap;
use Rebet\Database\Database;
use Rebet\Inflection\Inflector;

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
     * Meta information cache
     *
     * @var array
     */
    protected static $_meta = [];

    /**
     * Original data when fetched.
     *
     * @var static
     */
    protected $_origin = null;

    /**
     * Get and Set original data when fetched.
     *
     * @return self|null
     */
    public function origin(?Entity $origin = null) : ?self
    {
        return $origin === null ? $this->_origin : $this->_origin = $origin ;
    }

    /**
     * Get the table name of this entity.
     *
     * @return string
     */
    public static function tabelName() : string
    {
        if ($table = static::meta(__METHOD__)) {
            return $table;
        }

        $ac    = static::annotatedClass();
        $table = $ac->annotation(Table::class);
        return static::meta(__METHOD__, $table ? $table->value : Inflector::tableize($ac->reflector()->getShortName()));
    }

    /**
     * Get unmaps properties.
     *
     * @return array
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
     * Get default properties.
     *
     * @return array
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
     *
     * @return array
     */
    public function changes() : array
    {
        $changes = [];
        $unmaps  = static::unmaps();
        foreach ($this as $property => $value) {
            if (in_array($property, $unmaps)) {
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
     *
     * @return bool
     */
    public function isDirty() : bool
    {
        if ($this->_origin === null) {
            return true;
        }
        $unmaps = static::unmaps();
        foreach ($this as $property => $value) {
            if (in_array($property, $unmaps)) {
                continue;
            }
            if ($value !== $this->_origin->$property) {
                return true;
            }
        }
        return false;
    }

    /**
     * It check this entity is exists.
     *
     * @param Database|string|null $db (default: null)
     * @return boolean
     */
    public function exists($db = null) : bool
    {
        $where  = [];
        $params = [];
        foreach (static::primaryKeys() as $column) {
            $where[]         = "{$column} = :{$column}";
            $params[$column] = $this->origin() ? $this->origin()->$column : $this->$column ;
        }

        return static::db($db)->exists("SELECT * FROM ".static::tabelName()." WHERE ".join(' AND ', $where), $params);
    }

    /**
     * Create own data to given name database.
     *
     * @param Database|string|null $db (default: null)
     * @return bool
     */
    public function create($db = null) : bool
    {
        return static::db($db)->create($this);
    }

    /**
     * Update own changed data to given name database.
     *
     * @param Database|string|null $db (default: null)
     * @return bool
     */
    public function update($db = null) : bool
    {
        return static::db($db)->update($this);
    }

    /**
     * Save (Create/Update) own changed data to given name database.
     *
     * @param Database|string|null $db (default: null)
     * @return bool
     */
    public function save($db = null) : bool
    {
        return static::db($db)->save($this);
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
     * Find entity by given primaries
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

        return static::db($db)->find("SELECT * FROM ".static::tabelName()." WHERE ".join(' AND ', $where).($for_update ? ' FOR UPDATE' : ''), null, $params, get_called_class());
    }
}
