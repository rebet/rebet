<?php
namespace Rebet\View\Engine\Smarty;

/**
 * Smarty Wrapper Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class SmartyWrapper extends \Smarty
{
    /**
     * Normalize path
     *  - remove /./ and /../
     *  - make it absolute if required
     *  - replace \ to /
     *
     * @param string $path     file path
     * @param bool   $realpath if true - convert to absolute
     *                         false - convert to relative
     *                         null - keep as it is but
     *                         remove /./ /../
     *
     * @return string
     */
    public function _realpath($path, $realpath = null)
    {
        preg_match(
            '%^(?<root>(?:[[:alpha:]]:[\\\\/]|/|[\\\\]{2}[[:alpha:]]+|[[:print:]]{2,}:[/]{2}|[\\\\])?)(?<path>(.*))$%u',
            $path,
            $parts
        );
        $path = $parts[ 'path' ];
        if ($parts[ 'root' ] === '\\') {
            $parts[ 'root' ] = substr(getcwd(), 0, 2) . $parts[ 'root' ];
        } else {
            if ($realpath !== null && !$parts[ 'root' ]) {
                $path = getcwd() . '/' . $path;
            }
        }
        // normalize '/'
        $path            = str_replace('\\', '/', $path);
        $parts[ 'root' ] = str_replace('\\', '/', $parts[ 'root' ]);
        do {
            $path = preg_replace(
                ['#[\\\\/]{2}#', '#[\\\\/][.][\\\\/]#', '#[\\\\/]([^\\\\/.]+)[\\\\/][.][.][\\\\/]#'],
                '/',
                $path,
                -1,
                $count
            );
        } while ($count > 0);
        return $realpath !== false ? $parts[ 'root' ] . $path : str_ireplace(getcwd(), '.', $parts[ 'root' ] . $path);
    }
}
