<?php
namespace Rebet\Validation;

use Rebet\Common\Reflector;
use Rebet\Common\Strings;
use Rebet\Common\Utils;
use Rebet\Enum\Enum;
use Rebet\Inflection\Inflector;
use Rebet\Translation\Translator;

/**
 * Validate Context Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Context
{
    /**
     * Validation CRUD mode.
     *
     * @var string
     */
    private $crud = null;

    /**
     * The data under validation.
     *
     * @var array
     */
    private $data = [];

    /**
     * Validation target field prefix.
     *
     * @var string
     */
    public $prefix = '';

    /**
     * Validation target field name.
     *
     * @var string
     */
    public $field = null;

    /**
     * Label of validation target field.
     *
     * @var string
     */
    public $label = null;

    /**
     * Validation target value.
     *
     * @var mixed
     */
    public $value = null;

    /**
     * Validation errors
     *
     * @var array
     */
    private $errors = [];

    /**
     * Translator
     *
     * @var Translator
     */
    private $translator;

    /**
     * Validation label settings
     *
     * @var array
     */
    private $rules = [];

    /**
     * The parent context if this is nested context.
     *
     * @var Context
     */
    private $parent = null;

    /**
     * Key in nested context list
     *
     * @var string|int|null
     */
    public $key = null;

    /**
     * Quiet mode
     *
     * @var boolean
     */
    private $quiet = false;
    
    /**
     * Extra infomation for handle validation
     *
     * @var array
     */
    private $extra = [];

    /**
     * Create validation context instance.
     *
     * @param string $crud
     * @param array $data
     * @param array $errors
     * @param array $rules
     * @param Translator $translator
     */
    public function __construct(string $crud, array $data, array &$errors, array $rules, Translator $translator)
    {
        $this->crud       = $crud;
        $this->data       = $data;
        $this->errors     = &$errors;
        $this->translator = $translator;
        $this->rules      = $rules;
    }

    /**
     * It checks currently error is occurred
     * If you give '*' for field, that means checks all fields errors.
     *
     * @param string|null $field (default: current focused field)
     * @return boolean
     */
    public function hasError(?string $field = null) : bool
    {
        $field = $field ?? "{$this->prefix}{$this->field}" ;
        return $field === '*' ? !empty($this->errors) : isset($this->errors[$field]) ;
    }

    /**
     * It checks the context is quiet.
     * If quiet, that means validation error message is not output.
     *
     * @return boolean
     */
    public function isQuiet() : bool
    {
        return $this->quiet;
    }

    /**
     * Set quiet mode.
     *
     * @param boolean $quiet
     * @return self
     */
    public function quiet(bool $quiet) : self
    {
        $this->quiet = $quiet;
        return $this;
    }
    
    /**
     * Check validation target value (or given field) is blank
     *
     * @todo When Upload File
     *
     * @param string $field
     * @return boolean
     */
    public function blank(string $field = null) : bool
    {
        $value = $field ? $this->value($field) : $this->value ;
        return Utils::isBlank($value) ;
    }

    /**
     * Append validation error message using translator.
     * This method always return false, this behavior may be useful when implementing validations.
     *
     * If the key starts with "@", it uses the key name without the "@" as the message.
     * This is useful when implementing extended validation with a service where the locale can be fixed.
     *
     * If this method is called before initBy(), the message is stored in the message key 'global'.
     *
     * @param string $key
     * @param array $replace (default: [])
     * @return bool false
     */
    public function appendError(string $key, array $replace = [], $selector = null) : bool
    {
        $replace['attribute'] = $replace['attribute'] ?? $this->label;
        $replace['self']      = $replace['self'] ?? $this->value;
        $replace['selector']  = $selector;
        $prefix               = is_null($this->key) ? $this->prefix : "{$this->prefix}.{$this->key}" ;

        $this->errors[$this->field ? "{$prefix}{$this->field}" : 'global'][] = Strings::startsWith($key, '@') ? Strings::ltrim($key, '@') : $this->translator->get($key, $replace, $selector) ;
        return false;
    }

    /**
     * Initialize the context by the given field
     *
     * @param string $field
     * @param mixed|null $value (default: $this->value($field))
     * @param string|null $label (default: $this->label($field))
     * @return self
     */
    public function initBy(string $field, $value = null, string $label = null) : self
    {
        $this->field = $field;
        $this->value = $value ?? $this->value($field);
        $this->label = $label ?? $this->label($field);
        return $this;
    }

    /**
     * Get the value of given field
     *
     * @param string $field
     * @return mixed
     */
    public function value(string $field)
    {
        return Reflector::get($this->data, $field);
    }

    /**
     * Get the label of given field
     *
     * @param string $field
     * @return string
     */
    public function label(string $field) : string
    {
        $label  = '';
        $parent = '';
        $rule   = $this->rules;
        foreach (explode('.', "{$this->prefix}{$field}") as $parts) {
            $label  = $rule[$parts]['label'] ?? Inflector::humanize($parts);
            $label  = str_replace(':parent', $parent, $label);
            $parent = $label;
            $rule   = $rule[$parts]['nests'] ?? $rule[$parts]['nest'] ?? [];
        }

        return $label;
    }

    /**
     * Get the labels of given fields
     *
     * @param array $fields
     * @param string $delimiter (default: ',')
     * @return string
     */
    public function labels(array $fields, string $delimiter = ', ') : string
    {
        return implode($delimiter, array_map(function ($field) {
            return $this->label($field);
        }, $fields));
    }

    /**
     * Resolve value / :field_name string / Enum object
     *
     * @param mixed $value value or :field_name string or Enum object
     * @return array [$value, $label]
     */
    public function resolve($value) : array
    {
        if (!is_string($value) || !Strings::startsWith($value, ':')) {
            return [
                $value instanceof Enum ? $value->value : $value,
                $value instanceof Enum ? $value->label : $value,
            ];
        }
        $field = Strings::ltrim($value, ':');
        return [$this->value($field), $this->label($field)];
    }

    /**
     * Get the ordinalize number for current locale.
     * If the ordinalize for current locale is nothing then return given number as it is.
     *
     * @param integer $num
     * @return string
     */
    public function ordinalize(int $num) : string
    {
        return $this->translator->ordinalize($num);
    }

    /**
     * Get the grammar of given name for current locale of validation group.
     *
     * @param string $name
     * @param mixed $default (default: null)
     * @return mixed
     */
    public function grammar(string $name, $default = null)
    {
        return $this->translator->grammar('validation', $name, $default);
    }

    /**
     * Get Validation CRUD mode.
     *
     * @return string
     */
    public function crud() : string
    {
        return $this->crud;
    }

    /**
     * Get the parent context
     *
     * @return self|null
     */
    public function parent() : ?self
    {
        return $this->parent;
    }

    /**
     * It checks this context have parent.
     *
     * @return boolean
     */
    public function hasParent() : bool
    {
        return !is_null($this->parent);
    }

    /**
     * Create a nested context
     *
     * @param string|int|null $key
     * @return self
     */
    public function nest($key = null) : self
    {
        $nested         = clone $this;
        $nested->prefix = "{$this->prefix}{$this->field}.";
        $nested->data   = !is_null($key) ? $this->data[$this->field][$key] : $this->data[$this->field] ;
        $nested->key    = $key;
        $nested->parent = $this;
        $nested->filed  = null;
        $nested->lavel  = null;
        $nested->value  = null;
        $nested->quiet  = false;
        $nested->extra  = [];
        return $nested;
    }

    /**
     * Set the extra information for validation by given key
     *
     * @param string $key
     * @param [type] $value
     * @return self
     */
    public function setExtra(string $key, $value) : self
    {
        $this->extra[$this->field][$key] = $value;
        return $this;
    }

    /**
     * Get the extra information for validation by given key
     *
     * @param string $key
     * @return void
     */
    public function extra(string $key)
    {
        return $this->extra[$this->field][$key] ?? null;
    }
}
