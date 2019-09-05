<?php
namespace Rebet\Database\DataModel;

use Rebet\Annotation\AnnotatedClass;
use Rebet\Common\Describable;
use Rebet\Common\Popuratable;
use Rebet\Database\Annotation\PrimaryKey;
use Rebet\Database\Dao;
use Rebet\Database\Database;
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
    use Popuratable, Describable;

    /**
     * @var AnnotatedClass[]
     */
    protected static $annotated_class = [];

    /**
     * Meta information cache
     *
     * @var array
     */
    protected static $_meta = [];

    /**
     * Create an Data Model object
     */
    public function __construct()
    {
        $class = get_class($this);
        if (!isset(static::$annotated_class[$class])) {
            static::$annotated_class[$class] = new AnnotatedClass($class);
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
        if (isset(static::$annotated_class[$class])) {
            return static::$annotated_class[$class];
        }
        return static::$annotated_class[$class] = new AnnotatedClass($class);
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
     *
     * @param Database|string|null $db
     * @return Database
     */
    protected static function db($db) : Database
    {
        $db = $db ?? Dao::current() ?? Dao::db() ;
        return $db instanceof Database ? $db : Dao::db($db) ;
    }
}
