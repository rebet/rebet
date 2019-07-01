<?php
namespace Rebet\Database\Compiler;

use Rebet\Common\Strings;
use Rebet\Database\Database;
use Rebet\Database\Exception\DatabaseException;

/**
 * Builtin Compiler Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class BuiltinCompiler implements Compiler
{
    /**
     * {@inheritDoc}
     */
    public function compile(Database $db, string $sql, $params) : array
    {
        $pdo_params = [];
        foreach ($params as $key => $value) {
            if (!preg_match('/[a-zA-Z0-9_]+/', $key)) {
                throw DatabaseException::by("Invalid SQL query parameter key [ {$key} ], the key must be pattern of /[a-zA-Z0-9_]+/.");
            }
            if (Strings::contains($key, '__')) {
                throw DatabaseException::by("Invalid SQL query parameter key [ {$key} ], the key may not be contain '__'(combined two underscores).");
            }
            $key                   = ":{$key}";
            [$pdo_key, $pdo_param] = $this->convertParam($db, $key, $value);
            $pdo_params            = array_merge($pdo_params, $pdo_param);
            if ($pdo_key !== $key) {
                $sql = preg_replace("/{$key}(?=[^a-zA-Z0-9_]|$)/", $pdo_key, $sql);
            }
        }

        return [$sql, $pdo_params];
    }

    /**
     * {@inheritDoc}
     */
    public function convertParam(Database $db, string $key, $value) : array
    {
        $key = Strings::startsWith($key, ':') ? $key : ":{$key}" ;
        if (!is_array($value)) {
            return [$key, [$key => $db->convertToPdo($value)]];
        }

        $unfold_keys = [];
        $params      = [];
        $index       = 0;
        foreach ($value as $v) {
            $function = '?';
            if (is_array($v)) {
                [$function, $v] = $v;
            }
            $unfold_key          = "{$key}__{$index}";
            $params[$unfold_key] = $db->convertToPdo($v);
            $unfold_keys[]       = str_replace('?', $unfold_key, $function);
            $index++;
        }
        return [join(', ', $unfold_keys), $params];
    }
}
