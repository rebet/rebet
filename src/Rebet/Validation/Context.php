<?php
namespace Rebet\Validation;

use Rebet\Common\Utils;
use Rebet\Translation\Translator;
use Rebet\Common\Strings;
use Rebet\Inflector\Inflector;
use Rebet\Common\Arrays;
use Rebet\Common\Reflector;

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
     * Check validation target value is empty
     *
     * @todo When Upload File
     *
     * @return boolean
     */
    public function empty() : bool
    {
        return Utils::isBlank($this->value) ;
    }

    /**
     * Append validation error message using translator.
     *
     * If the key starts with "@", it uses the key name without the "@" as the message.
     * This is useful when implementing extended validation with a service where the locale can be fixed.
     *
     * If this method is called before initBy(), the message is stored in the message key 'global'.
     *
     * @param string $key
     * @param array $replace (default: [])
     * @return self
     */
    public function appendError(string $key, array $replace = []) : self
    {
        $replace['label'] = $this->label;
        $replace['value'] = $this->value;
        $prefix           = is_null($this->key) ? $this->prefix : "{$this->prefix}.{$this->key}" ;
        $this->errors[$this->field ? "{$prefix}{$this->field}" : 'global'][] = Strings::startsWith($key, '@') ? Strings::ltrim($key, '@') : $this->translator->get($key, $replace) ;
        return $this;
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

        return $label ;
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
     * @return Context|null
     */
    public function parent() : ?Context
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
     * @return Context
     */
    public function nest($key = null) : Context
    {
        $nested = clone $this;
        $nested->prefix = "{$this->prefix}{$this->field}.";
        $nested->data   = !is_null($key) ? $this->data[$this->field][$key] : $this->data[$this->field] ;
        $nested->key    = $key;
        $nested->parent = $this;
        $nested->filed  = null;
        $nested->lavel  = null;
        $nested->value  = null;
        return $nested;
    }
}
