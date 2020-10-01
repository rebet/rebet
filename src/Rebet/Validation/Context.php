<?php
namespace Rebet\Validation;

use Rebet\Tools\Arrays;
use Rebet\Tools\Reflector;
use Rebet\Tools\Strings;
use Rebet\Tools\Utils;
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
     * Error key prefix of validation errors
     *
     * @var string
     */
    private $error_prefix = '';

    /**
     * Validation label settings
     *
     * @var array
     */
    private $rules = [];

    /**
     * Nested attribute auto format or not.
     *
     * @var boolean
     */
    private $nested_attribute_auto_format = true;

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
     * @param bool $nested_attribute_auto_format (default: true)
     */
    public function __construct(string $crud, array $data, array &$errors, array $rules, bool $nested_attribute_auto_format = true)
    {
        $this->crud                         = $crud;
        $this->data                         = $data;
        $this->errors                       = &$errors;
        $this->rules                        = $rules;
        $this->nested_attribute_auto_format = $nested_attribute_auto_format;
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
     * Check current value (or given field) is blank.
     *
     * @param string $field
     * @return boolean
     */
    public function blank(string $field = null) : bool
    {
        $value = $field ? $this->value($field) : $this->value ;
        return static::isBlank($value);
    }

    /**
     * Check the given value is blank.
     *
     * @todo When Upload File
     *
     * @param mixed $value
     * @return boolean
     */
    public static function isBlank($value) : bool
    {
        return Utils::isBlank($value) ;
    }

    /**
     * Get count of current value (or given field) items.
     *
     * @param string $field
     * @return integer
     */
    public function count(string $field = null) : int
    {
        return $this->blank($field) ? 0 : Arrays::count($this->value($field)) ;
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
        if ($this->isQuiet()) {
            return false;
        }

        $replace['attribute'] = $replace['attribute'] ?? $this->label;
        $replace['self']      = $replace['self'] ?? $this->value;
        $replace['selector']  = $selector;

        $message = null;
        if (Strings::startsWith($key, '@')) {
            $message = Translator::replace(Strings::lcut($key, 1), $replace, Translator::grammar('validation', 'delimiter', ', '));
        } else {
            $message = Translator::replace($this->message($key), $replace, Translator::grammar('validation', 'delimiter', ', ')) ?? Translator::get("validation.{$this->prefix}{$this->field}.{$key}", $replace, $selector) ;
        }

        $this->errors[$this->field ? "{$this->error_prefix}{$this->field}" : 'global'][] = $message;
        return false;
    }

    /**
     * Initialize the context by the given field
     *
     * @param string $field
     * @return self
     */
    public function initBy(string $field) : self
    {
        $this->field = $field;
        $this->value = $this->value($field);
        $this->label = $this->label($field);
        return $this;
    }

    /**
     * Get the value of given field
     *
     * @param string|null $field
     * @return mixed
     */
    public function value(?string $field)
    {
        return $field ? Reflector::get($this->data, $field) : $this->value ;
    }

    /**
     * Get the label of given field
     *
     * @param string $field
     * @return string
     */
    public function label(string $field) : string
    {
        $label = $this->labelTranslate("{$this->prefix}{$field}");
        if ($label) {
            return $this->parent ? $this->formatNestedAttributeLabel($label, $this->parent->label)  : $label ;
        }
        $parent = '';
        $rule   = $this->rules;
        foreach (explode('.', "{$this->prefix}{$field}") as $parts) {
            $label  = $rule[$parts]['label'] ?? Inflector::humanize($parts) ;
            $label  = str_replace(':parent', $parent, $label);
            $parent = $label;
            $rule   = $rule[$parts]['nests'] ?? $rule[$parts]['nest'] ?? [];
        }

        return $this->parent ? $this->formatNestedAttributeLabel($label, $this->parent->label)  : $label ;
    }

    /**
     * Format nested attribute label name using 'validation.@nested_attribute_format' grammer when the format was define.
     *
     * @param string $label
     * @param string $parent_label
     * @return string
     */
    protected function formatNestedAttributeLabel(string $label, string $parent_label) : string
    {
        if (!$this->nested_attribute_auto_format) {
            return $label;
        }
        $nested_attribute_format = $this->grammar('nested_attribute_format');
        return empty($nested_attribute_format) || Strings::contains($label, $parent_label) ? $label : str_replace([':attribute', ':nested_attribute'], [$parent_label, $label], $nested_attribute_format) ;
    }

    /**
     * Get the current field custom message of given key in rules.
     *
     * @param string $key
     * @return string|null
     */
    protected function message(string $key) : ?string
    {
        $rule = $this->rules;
        if ($this->prefix) {
            foreach (explode('.', trim($this->prefix, '.')) as $parts) {
                $rule = $rule[$parts]['nests'] ?? $rule[$parts]['nest'] ?? [];
            }
        }
        if (!isset($rule[$this->field]['messages'])) {
            return null;
        }
        $key = Strings::ratrim($key, ':');
        foreach ($rule[$this->field]['messages'] as $target => $value) {
            if ($key === Strings::ratrim($target, ':')) {
                return $value;
            }
        }
        return null;
    }

    /**
     * Get the label of given field from attribute translation resource.
     *
     * @param string $field
     * @return string|null
     */
    protected function labelTranslate(string $field) : ?string
    {
        $label = Translator::get("attribute.{$field}", [], null, false);
        if ($label !== null) {
            if (Strings::contains($label, ':parent') && Strings::contains($field, '.')) {
                $parent = $this->labelTranslate(Strings::ratrim($field, '.'));
                return str_replace(':parent', $parent, $label);
            }
            return $label;
        }
        if (Strings::contains($field, '.')) {
            return $this->labelTranslate(Strings::lbtrim($field, '.'));
        }
        return null;
    }

    /**
     * Get the labels of given fields
     *
     * @param array $fields
     * @param string|null $delimiter (default: depend on configure)
     * @return string
     */
    public function labels(array $fields, ?string $delimiter = null) : string
    {
        $delimiter = $delimiter ?? $this->grammar('delimiter', ', ');
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
                $value instanceof Enum ? $value->translate('label') : $value,
            ];
        }
        $field = Strings::lcut($value, 1);
        return [$this->value($field), $this->label($field)];
    }

    /**
     * Pluck the nested field values as array.
     *
     * @param string|null $nested_field
     * @return array [$list, $label]
     */
    public function pluckNested(?string $nested_field) : array
    {
        if ($nested_field) {
            $label = $this->formatNestedAttributeLabel($this->label("{$this->field}.{$nested_field}"), $this->label);
            $list  = array_map(function ($value) use ($nested_field) { return Reflector::get($value, $nested_field); }, (array)$this->value);
            return [$list, $label];
        }

        return [(array)$this->value, $this->label];
    }

    /**
     * Pluck the correlated fields value and label.
     *
     * @param array $fields
     * @return array [$field => ['field' => $field, 'value' => $value, 'label' => $label], ...]
     */
    public function pluckCorrelated(array $fields) : array
    {
        $list = [];
        foreach ($fields as $field) {
            $list[$field] = [
                'field' => $field,
                'value' => $this->value($field),
                'label' => $this->label($field),
            ];
        }
        return $list;
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
        return Translator::ordinalize($num);
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
        return Translator::grammar('validation', $name, $default);
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
        $nested               = clone $this;
        $nested->prefix       = "{$this->prefix}{$this->field}.";
        $nested->data         = !is_null($key) ? $this->data[$this->field][$key] : $this->data[$this->field] ;
        $nested->key          = $key;
        $nested->error_prefix = "{$this->error_prefix}{$this->field}.".(!is_null($key) ? "{$key}." : "");
        $nested->parent       = $this;
        $nested->filed        = null;
        $nested->lavel        = null;
        $nested->value        = null;
        $nested->quiet        = false;
        $nested->extra        = [];
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
