<?php
namespace Rebet\Tools\Reflection;

/**
 * Describable Trait
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
trait Describable
{
    /**
     * It copies value to the given dest object.
     * This method have some options like below,
     *
     * [aliases] : Copies property value from different name property and key.
     * --------------------------------------
     * 'aliases' => [
     *     'dest_name' => 'altanate_src_name',
     * ],
     *
     *
     * [includes] : Copies property value only given includes properties.
     * --------------------------------------
     * 'includes' => [
     *     'name',
     * ],
     *
     *
     * [excludes] : Copies other than given property value.
     * --------------------------------------
     * 'excludes' => [
     *     'name',
     * ],
     *
     * @param object $dest
     * @param array $option
     * @return object of injected dest
     */
    public function inject(&$dest, array $option = [])
    {
        foreach ($dest as $field => $origin) {
            Reflector::set($dest, $field, $this->applyDescribeOption($option, $this, $field, $origin), true);
        }

        return $dest;
    }

    /**
     * It creates the given dest class object and copies own value.
     * This method have some options like below,
     *
     * [aliases] : Copies property value from different name property and key.
     * --------------------------------------
     * 'aliases' => [
     *     'dest_name' => 'altanate_src_name',
     * ],
     *
     *
     * [includes] : Copies property value only given includes properties.
     * --------------------------------------
     * 'includes' => [
     *     'name',
     * ],
     *
     *
     * [excludes] : Copies other than given property value.
     * --------------------------------------
     * 'excludes' => [
     *     'name',
     * ],
     *
     * @param string $class
     * @param array $option
     * @return object of given class
     */
    public function describe(string $class, array $option = [])
    {
        $entity = new $class();
        return $this->inject($entity, $option);
    }

    /**
     * Apply Option
     *
     * @param array $option
     * @param string $field
     * @param array|object $src
     * @param mixed $origin
     * @return void
     */
    protected function applyDescribeOption(array $option, $src, string $field, $origin)
    {
        $defined = Reflector::has($src, $field, true);
        $value   = Reflector::get($src, $field, null, true);
        if (empty($option)) {
            return $defined ? $value : $origin ;
        }

        if (array_key_exists($field, $option['aliases'] ?? [])) {
            $alias = $option['aliases'][$field];
            if ($alias === null) {
                return $origin;
            }
            $value = Reflector::get($src, $alias, null, true);
            $field = $alias;
        }

        $includes = $option['includes'] ?? null;
        if ($includes) {
            if (!in_array($field, $includes)) {
                return $origin;
            }
        } else {
            $excludes = $option['excludes'] ?? null;
            if ($excludes) {
                if (in_array($field, $excludes)) {
                    return $origin;
                }
            }
        }

        return $defined ? $value : $origin ;
    }
}
