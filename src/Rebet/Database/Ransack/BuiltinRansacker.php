<?php
namespace Rebet\Database\Ransack;

use Rebet\Common\Utils;
use Rebet\Config\Configurable;
use Rebet\Database\Database;

/**
 * Builtin Ransacker Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class BuiltinRansacker implements Ransacker
{
    use Configurable;

    public static function defaultConfig()
    {
        return [
            'extension' => null, // function(Database $db, Ransack $ransack) : ?array
        ];
    }

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
    public function convert($ransack_predicate, $value, array $alias = [], ?\Closure $extension = null) : ?array
    {
        //  1 | If value is blank(null, '' or []) then ransack will be ignored
        if (Utils::isBlank($value)) {
            return null;
        }

        //  2 | Join sub ransack conditions by 'OR'.
        if (is_int($ransack_predicate)) {
            $where  = [];
            $params = [];
            foreach ($value as $sub_conditions) {
                $sub_where  = [];
                $sub_params = [];
                foreach ($sub_conditions as $k => $v) {
                    [$expression, $nv] = $this->convert($k, $v, $alias, $extension);
                    if ($expression) {
                        $sub_where[] = $expression;
                        $sub_params  = array_merge($sub_params, $nv);
                    }
                }
                $where[] = '('.implode(' AND', $sub_where).')';
                $params  = array_merge($params, $sub_params);
            }
            return ['('.implode(' OR', $where).')', $params];
        }

        $ransack = Ransack::analyze($this->db, $ransack_predicate, $value, $alias);

        //  3 | Custom predicate by ransack extension closure for each convert
        if ($extension && $custom = $extension($this->db, $ransack)) {
            return $custom;
        }

        //  4 | Custom predicate by ransack extension configure for all convert
        $base_extension = static::config('extension', false);
        if ($base_extension && $custom = $base_extension($this->db, $ransack)) {
            return $custom;
        }

        return $ransack->convert();
    }
}
