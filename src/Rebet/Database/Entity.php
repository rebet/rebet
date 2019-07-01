<?php
namespace Rebet\Database;

use Rebet\Database\Annotation\PrimaryKey;
use Rebet\Database\Annotation\Table;
use Rebet\Database\Annotation\Unmap;
use Rebet\Inflection\Inflector;

/**
 * Entity Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
abstract class Entity extends Dto
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
     * Get and Set meta data.
     *
     * @param string $name
     * @param mixed $value
     * @return mixed
     */
    protected function meta(string $name, $value = null)
    {
        $class = get_class($this);
        if ($value === null) {
            return static::$_meta[$class][$name] ?? null ;
        }

        static::$_meta[$class][$name] = $value;
        return $value;
    }

    /**
     * Get the table name of this entity.
     *
     * @return string
     */
    public function tabelName() : string
    {
        if ($table = $this->meta(__METHOD__)) {
            return $table;
        }

        $ac    = $this->annotatedClass();
        $table = $ac->annotation(Table::class);
        return $this->meta(__METHOD__, $table ? $table->value : Inflector::tableize($ac->reflector()->getShortName()));
    }

    /**
     * Get unmaps properties.
     *
     * @return array
     */
    public function unmaps() : array
    {
        if ($unmaps = $this->meta(__METHOD__)) {
            return $unmaps;
        }

        $unmaps     = [];
        $ac         = $this->annotatedClass();
        foreach ($this as $property => $value) {
            $ap = $ac->property($property);
            if (!$ap->reflector()->isPublic() || $ap->annotation(Unmap::class)) {
                $unmaps[] = $property;
            }
        }
        return $this->meta(__METHOD__, $unmaps);
    }

    /**
     * Get primary keys properties.
     *
     * @return array
     */
    public function primaryKeys() : array
    {
        if ($primary_keys = $this->meta(__METHOD__)) {
            return $primary_keys;
        }

        $primary_keys = [];
        $ac           = $this->annotatedClass();
        foreach ($this as $property => $value) {
            if ($ac->property($property)->annotation(PrimaryKey::class)) {
                $primary_keys[] = $property;
            }
        }

        if (empty($primary_keys)) {
            $pkey = Inflector::primarize($ac->reflector()->getShortName());
            if (property_exists($this, $pkey)) {
                $primary_keys[] = $pkey;
            }
        }

        return $this->meta(__METHOD__, $primary_keys);
    }

    /**
     * Get the property and value list that changed.
     *
     * @return array
     */
    public function changes() : array
    {
        $changes = [];
        $unmaps  = $this->unmaps();
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
        $unmaps = $this->unmaps();
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
     * @param string|null $db (default: null)
     * @return boolean
     */
    public function exists(?string $db = null) : bool
    {
        $where  = [];
        $params = [];
        foreach ($this->primaryKeys() as $column) {
            $where[]                = "{$column} = :c\${$column}";
            $params["c\${$column}"] = $this->origin() ? $this->origin()->$column : $this->$column ;
        }

        return Dao::db($db)->exists("SELECT * FROM ".$this->tabelName()." WHERE ".join(' AND ', $where), $params);
    }

    /**
     * Create own data to given name database.
     *
     * @param string|null $db
     * @return bool
     */
    public function create(?string $db = null) : bool
    {
        return Dao::db($db)->create($this);
    }

    /**
     * Update own changed data to given name database.
     *
     * @param string|null $db
     * @return bool
     */
    public function update(?string $db = null) : bool
    {
        return Dao::db($db)->update($this);
    }

    /**
     * Save (Create/Update) own changed data to given name database.
     *
     * @param string|null $db
     * @return bool
     */
    public function save(?string $db = null) : bool
    {
        return Dao::db($db)->save($this);
    }

    /**
     * Delete own changed data to given name database.
     * @todo support soft delete
     *
     * @param string|null $db
     * @return bool
     */
    public function delete(?string $db = null) : bool
    {
        return Dao::db($db)->delete($this);
    }
}
