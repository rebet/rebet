<?php
namespace Rebet\Log\Driver\Monolog\Formatter;

use Monolog\Formatter\FormatterInterface;
use Rebet\Tools\Arrays;
use Rebet\Tools\Callback;
use Rebet\Tools\Reflection\Reflector;
use Rebet\Tools\Strings;
use Rebet\Tools\Config\Configurable;

/**
 * Text Formatter Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class TextFormatter implements FormatterInterface
{
    use Configurable;

    public static function defaultConfig()
    {
        return [
            'default_format'      => "{datetime} {channel}/{extra.process_id} [{level_name}] {message}{context}{extra}{exception}\n",
            'default_stringifier' => function ($val, array $masks, string $masked_label) { return Strings::stringify($val, $masks, $masked_label); },
            'stringifiers'        => [
                '{datetime}'  => function ($val, array $masks, string $masked_label) { return $val->format('Y-m-d H:i:s.u'); },
                '{context}'   => function ($val, array $masks, string $masked_label) { return empty($val) ? '' : "\n====== [  CONTEXT  ] ======\n".Strings::indent(Strings::stringify($val, $masks, $masked_label), "== ") ; },
                '{extra}'     => function ($val, array $masks, string $masked_label) { return empty($val) ? '' : "\n------ [   EXTRA   ] ------\n".Strings::indent(Strings::stringify($val, $masks, $masked_label), "-- ") ; },
                '{exception}' => function ($val, array $masks, string $masked_label) { return empty($val) ? '' : "\n****** [ EXCEPTION ] ******\n".Strings::indent("{$val}", "** ") ; },
            ],
            'masks'               => [],
            'masked_label'        => '********',
        ];
    }

    /**
     * @var string of log format
     */
    protected $format;

    /**
     * @var callable[]
     */
    protected $stringifiers;

    /**
     * Create Text formatter.
     *
     * @param string $format (default: depend on configure)
     * @param string $stringifiers (default: depend on configure)
     */
    public function __construct(?string $format = null, array $stringifiers = [])
    {
        $this->format       = $format ?? static::config('default_format');
        $this->stringifiers = array_merge(static::config('stringifiers', false, []), $stringifiers);
    }

    /**
     * Stringify the given value using stringifier for given key.
     *
     * @param string $key
     * @param mixed $val
     * @return string
     */
    protected function stringify(string $key, $val) : string
    {
        return Reflector::evaluate($this->stringifiers[$key] ?? static::config('default_stringifier'), [$val, static::config('masks', false, []), static::config('masked_label')], true);
    }

    /**
     * {@inheritDoc}
     */
    public function format(array $record)
    {
        $output    = $this->format;
        $exception = Reflector::remove($record, 'context.exception');

        foreach (Arrays::sortKeys($record['extra'] ?? [], SORT_DESC, Callback::compareLength()) as $var => $val) {
            $key = '{extra.'.$var.'}';
            if (false !== strpos($output, $key)) {
                $output = str_replace($key, $this->stringify($key, $val), $output);
                unset($record['extra'][$var]);
            }
        }

        foreach (Arrays::sortKeys($record['context'] ?? [], SORT_DESC, Callback::compareLength()) as $var => $val) {
            $key = '{context.'.$var.'}';
            if (false !== strpos($output, $key)) {
                $output = str_replace($key, $this->stringify($key, $val), $output);
                unset($record['context'][$var]);
            }
        }

        foreach (Arrays::sortKeys($record, SORT_DESC, Callback::compareLength()) as $var => $val) {
            $key = '{'.$var.'}';
            if (false !== strpos($output, $key)) {
                $output = str_replace($key, $this->stringify($key, $val), $output);
            }
        }

        if (false !== strpos($output, '{exception}')) {
            $output = str_replace('{exception}', $this->stringify('{exception}', $exception), $output);
        }

        // remove leftover {extra.xxx} and {context.xxx} if any
        if (false !== strpos($output, '{')) {
            $output = preg_replace('/\{(?:extra|context)\..+?}/', '', $output);
        }

        return $output;
    }

    /**
     * {@inheritDoc}
     */
    public function formatBatch(array $records)
    {
        $message = '';
        foreach ($records as $record) {
            $message .= $this->format($record);
        }
        return $message;
    }
}
