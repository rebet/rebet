<?php
namespace Rebet\Validation;

use Rebet\Config\Configurable;
use Rebet\File\Files;
use Rebet\Config\Config;
use Rebet\Config\LocaleResource;
use Rebet\Translation\Translator;
use Rebet\Translation\FileLoader;
use Rebet\Common\Collection;
use Rebet\Common\Reflector;
use Rebet\Common\Strings;
use Rebet\Common\Arrays;

/**
 * Validator Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class Validator
{
    use Configurable;
    public static function defaultConfig()
    {
        return [
            'resources_dir' => [Files::normalizePath(__DIR__.'/i18n')],
            'validations'   => [],
        ];
    }

    /**
     * Translator
     *
     * @var Translator
     */
    protected $translator;

    /**
     * The data under validation.
     *
     * @var array
     */
    protected $data = [];

    /**
     * Validation errors
     *
     * @var array
     */
    protected $errors = [];

    /**
     * Create a new Validator instance.
     *
     * @param array $data
     * @param Translator $translator (default: new Translator(new FileLoader(static::config('resources_dir'))))
     */
    public function __construct(array $data, Translator $translator = null)
    {
        $this->data       = $data;
        $this->translator = $translator ?? new Translator(new FileLoader(static::config('resources_dir'))) ;
    }

    /**
     * Validate the data by given crud type rules.
     *
     * @param string $crud
     * @param array|Rule $rules
     * @return ValidData|null
     */
    public function validate(string $crud, $rules) : ?ValidData
    {
        $rules            = is_string($rules) ? Reflector::instantiate($rules) : $rules ;
        $custom_validator = null;
        if ($rules instanceof Rule) {
            $custom_validator = $rules;
            $rules            = $rules->rules();
        }
        
        $context    = new Context($crud, $this->data, $this->errors, $rules, $this->translator);
        $valid_data = $this->_validate($context, $rules, $custom_validator);
        return $context->hasError('*') ? null : $valid_data ;
    }

    /**
     * Validate the data by given context and rules for recursive.
     *
     * @param Context $context
     * @param array $rules
     * @param Rule|null $custom_validator
     * @return Collection|null
     */
    protected function _validate(Context $context, $rules, ?Rule $custom_validator) : ValidData
    {
        $valid_data = [];
        foreach ($rules as $field => $config) {
            // Init context
            $context->initBy($field);

            // Handle before filter
            $before = (array)($config['before'] ?? []);
            foreach ($before as $filter) {
                $context->value = $filter($context->value);
            }

            // Handle validation rules
            $this->validateRules($context, $config['rule'] ?? [], $custom_validator);
            $data  = null;
            $nest  = $config['nest']  ?? [] ;
            $nests = $config['nests'] ?? [] ;
            if ($nest) {
                $data = $this->_validate($context->nest(), $nest, $custom_validator);
            } elseif ($nests) {
                $data = [];
                foreach (array_keys($context->value) as $key) {
                    $data[$key] = $this->_validate($context->nest($key), $nests, $custom_validator);
                }
                $data = new Collection($data);
            } else {
                $data = $context->value;
            }
            if ($context->hasError()) {
                continue;
            }
            
            // Handle convert
            $convert = $config['convert'] ?? null;
            if ($convert) {
                $converted = Reflector::convert($data, $convert);
                if (is_null($converted) && !is_null($data)) {
                    $context->appendError('', ['convert' => $convert]); // @todo 要実装
                }
                $data = $converted;
            }

            // Handle after filter
            $after = (array)($config['after'] ?? []);
            foreach ($after as $filter) {
                $data = $filter($data);
            }

            $valid_data[$context->field] = $data;
        }
        return new ValidData($valid_data);
    }

    /**
     * Handle validation rules
     *
     * @param Context $context
     * @param array $rules
     * @param Rule|null $custom_validator
     */
    protected function validateRules(Context $context, array $rules, ?Rule $custom_validator) : void
    {
        if (!is_array($rules)) {
            throw new \LogicException("Invalid rules format. A 'rule/then/else' list should be array.");
        }
        foreach ($rules as $rule) {
            if (!is_array($rule)) {
                throw new \LogicException("Invalid rules format. A 'rule/then/else' list item should be array.");
            }
            $crud = array_shift($rule);
            if (!Strings::contains($crud, $context->crud())) {
                continue;
            }
            $validation = array_shift($rule);
            $args       = [];
            foreach ($rule as $key => $value) {
                if (is_int($key)) {
                    $args[] = $value;
                }
            }
    
            [$name, $option] = array_pad(explode(':', $validation), 2, '');
            if (Strings::contains($option, '?')) {
                $context->quiet(true);
            }
            $valid = false;
            if ($custom_validator && $custom_validator->hasCustomValidation($name)) {
                $valid = $custom_validator->validate($name, $context, ...$args);
            } else {
                $global_validator = static::config("validations.{$name}", false, null);
                $method = "validation{$name}";
                $valid  = $global_validator ? call_user_func($global_validator, $context, ...$args) : $this->$method($context, ...$args) ;
            }
            $context->quiet(false);
    
            if (!$valid && Strings::contains($option, '!')) {
                return;
            }

            $then = $rule['then'] ?? null;
            $else = $rule['else'] ?? null;
            if ($valid && $then) {
                $this->validateRules($context, $then, $custom_validator);
            }
            if (!$valid && $else) {
                $this->validateRules($context, $else, $custom_validator);
            }
        }
    }

    /**
     * Add global validation to validator.
     *
     * @param string $name
     * @param \Closure $validation
     * @return void
     */
    public static function addValidation(string $name, \Closure $validation) : void
    {
        static::setConfig(['validations' => [$name => $validation]]);
    }

    /**
     * Get the validation errors.
     *
     * @return array
     */
    public function errors() : array
    {
        return $this->errors;
    }


    // ====================================================
    // Built-in Validation Methods
    // ====================================================
    /**
     * Satisfy validation/condition
     *
     * @param Context $c
     * @param \Closure $test
     * @return boolean
     */
    protected function validationSatisfy(Context $c, \Closure $test) : bool
    {
        return $test($c);
    }

    /**
     * Required Validation
     *
     * @param Context $c
     * @return boolean
     */
    protected function validationRequired(Context $c) : bool
    {
        if ($c->blank()) {
            $c->appendError('validation.Required');
            return false;
        }
        return true;
    }

    /**
     * Required If Validation
     *
     * @param Context $c
     * @param string $other field name
     * @param mixed $value value or array or :field_name
     * @return boolean
     */
    protected function validationRequiredIf(Context $c, string $other, $value) : bool
    {
        if (!$c->blank()) {
            return true;
        }
        return static::handleIf($c, $other, $value, function ($c, $other, $value, $label) {
            $c->appendError('validation.RequiredIf', ['other' => $c->label($other), 'value' => $label], Arrays::count($value));
            return false;
        });
    }
    
    /**
     * Required Unless Validation
     *
     * @param Context $c
     * @param string $other field name
     * @param mixed $value value or array or :field_name
     * @return boolean
     */
    protected function validationRequiredUnless(Context $c, string $other, $value) : bool
    {
        if (!$c->blank()) {
            return true;
        }
        return static::handleUnless($c, $other, $value, function ($c, $other, $value, $label) {
            $c->appendError('validation.RequiredUnless', ['other' => $c->label($other), 'value' => $label], Arrays::count($value));
            return false;
        });
    }
    
    /**
     * Handle If validate precondition
     *
     * @param Context $c
     * @param string $other
     * @param string|array $value value or array or :field_name
     * @param callable $callback function(Context $c, string $other, $value, string $label) { ... }
     * @return boolean
     */
    public static function handleIf(Context $c, string $other, $value, callable $callback) : bool
    {
        [$value, $label] = $c->resolve($value);
        if (in_array($c->value($other), is_null($value) ? [null] : (array)$value)) {
            return $callback($c, $other, $value, $label);
        }
        return true;
    }

    /**
     * Handle Unless validate precondition
     *
     * @param Context $c
     * @param string $other
     * @param string|array $value value or array or :field_name
     * @param callable $callback function(Context $c, string $other, $value, string $label) { ... }
     * @return boolean
     */
    public static function handleUnless(Context $c, string $other, $value, callable $callback) : bool
    {
        [$value, $label] = $c->resolve($value);
        if (!in_array($c->value($other), is_null($value) ? [null] : (array)$value)) {
            return $callback($c, $other, $value, $label);
        }
        return true;
    }
    
    /**
     * Required With Validation
     *
     * @param Context $c
     * @param string|array $other field names
     * @param int|null $at_least (default: null)
     * @return boolean
     */
    protected function validationRequiredWith(Context $c, $other, ?int $at_least = null) : bool
    {
        if (!$c->blank()) {
            return true;
        }
        return static::handleWith($c, $other, $at_least, function ($c, $other, $at_least, $max, $inputed) {
            $c->appendError(
                'validation.RequiredWith',
                ['other' => $c->labels($other), 'at_least' => $at_least],
                Arrays::count($other) === 1 ? 'one' : ($at_least < $max ? 'some' : 'all')
            );
            return false;
        });
    }

    /**
     * Required Without Validation
     *
     * @param Context $c
     * @param string|array $other field names
     * @param int|null $at_least (default: null)
     * @return boolean
     */
    protected function validationRequiredWithout(Context $c, $other, ?int $at_least = null) : bool
    {
        if (!$c->blank()) {
            return true;
        }
        return static::handleWithout($c, $other, $at_least, function ($c, $other, $at_least, $max, $not_inputed) {
            $c->appendError(
                'validation.RequiredWithout',
                ['other' => $c->labels($other), 'at_least' => $at_least],
                Arrays::count($other) === 1 ? 'one' : ($at_least < $max ? 'some' : 'all')
            );
            return false;
        });
    }
    
    /**
     * Handle With validate precondition
     *
     * @param Context $c
     * @param string|array $other
     * @param integer|null $at_least
     * @param callable $callback function(Context $c, $other, ?int $at_least, int $max, int $inputed){ ... }
     * @return boolean
     */
    public static function handleWith(Context $c, $other, ?int $at_least, callable $callback) : bool
    {
        $other    = (array)$other;
        $max      = count($other);
        $at_least = $at_least ?? $max;
        $inputed  = 0;
        foreach ($other as $field) {
            $inputed += $c->blank($field) ? 0 : 1 ;
        }
        if ($inputed >= $at_least) {
            return $callback($c, $other, $at_least, $max, $inputed);
        }

        return true;
    }

    /**
     * Handle Without validate precondition
     *
     * @param Context $c
     * @param string|array $other
     * @param integer|null $at_least
     * @param callable $callback function(Context $c, $other, ?int $at_least, int $max, int $not_inputed){ ... }
     * @return boolean
     */
    public static function handleWithout(Context $c, $other, ?int $at_least, callable $callback) : bool
    {
        $other       = (array)$other;
        $max         = count($other);
        $at_least    = $at_least ?? $max;
        $not_inputed = 0;
        foreach ($other as $field) {
            $not_inputed += $c->blank($field) ? 1 : 0 ;
        }
        if ($not_inputed >= $at_least) {
            return $callback($c, $other, $at_least, $max, $not_inputed);
        }

        return true;
    }
    
    /**
     * Blank If Validation
     *
     * @param Context $c
     * @param string $other field name
     * @param mixed $value value or array or :field_name
     * @return boolean
     */
    protected function validationBlankIf(Context $c, string $other, $value) : bool
    {
        if ($c->blank()) {
            return true;
        }
        return static::handleIf($c, $other, $value, function ($c, $other, $value, $label) {
            $c->appendError('validation.BlankIf', ['other' => $c->label($other), 'value' => $label], Arrays::count($value));
            return false;
        });
    }
    
    /**
     * Blank Unless Validation
     *
     * @param Context $c
     * @param string $other field name
     * @param mixed $value value or array or :field_name
     * @return boolean
     */
    protected function validationBlankUnless(Context $c, string $other, $value) : bool
    {
        if ($c->blank()) {
            return true;
        }
        return static::handleUnless($c, $other, $value, function ($c, $other, $value, $label) {
            $c->appendError('validation.BlankUnless', ['other' => $c->label($other), 'value' => $label], Arrays::count($value));
            return false;
        });
    }

    /**
     * Blank With Validation
     *
     * @param Context $c
     * @param string|array $other field names
     * @param int|null $at_least (default: null)
     * @return boolean
     */
    protected function validationBlankWith(Context $c, $other, ?int $at_least = null) : bool
    {
        if ($c->blank()) {
            return true;
        }
        return static::handleWith($c, $other, $at_least, function ($c, $other, $at_least, $max, $inputed) {
            $c->appendError(
                'validation.BlankWith',
                ['other' => $c->labels($other), 'at_least' => $at_least],
                Arrays::count($other) === 1 ? 'one' : ($at_least < $max ? 'some' : 'all')
            );
            return false;
        });
    }

    /**
     * Blank Without Validation
     *
     * @param Context $c
     * @param string|array $other field names
     * @param int|null $at_least (default: null)
     * @return boolean
     */
    protected function validationBlankWithout(Context $c, $other, ?int $at_least = null) : bool
    {
        if ($c->blank()) {
            return true;
        }
        return static::handleWithout($c, $other, $at_least, function ($c, $other, $at_least, $max, $not_inputed) {
            $c->appendError(
                'validation.BlankWithout',
                ['other' => $c->labels($other), 'at_least' => $at_least],
                Arrays::count($other) === 1 ? 'one' : ($at_least < $max ? 'some' : 'all')
            );
            return false;
        });
    }

    /**
     * Same As Validation
     *
     * @param Context $c
     * @param mixed $value
     * @return boolean
     */
    protected function validationSameAs(Context $c, $value) : bool
    {
        if ($c->blank()) {
            return true;
        }
        [$value, $label] = $c->resolve($value);
        if ($c->value == $value) {
            return true;
        }
        $c->appendError('validation.SameAs', ['value' => $label]);
        return false;
    }

    /**
     * Not Same As Validation
     *
     * @param Context $c
     * @param mixed $value
     * @return boolean
     */
    protected function validationNotSameAs(Context $c, $value) : bool
    {
        if ($c->blank()) {
            return true;
        }
        [$value, $label] = $c->resolve($value);
        if ($c->value != $value) {
            return true;
        }
        $c->appendError('validation.NotSameAs', ['value' => $label]);
        return false;
    }

    /**
     * Regex Validation
     *
     * @param Context $c
     * @param string $pattern
     * @param string $selector (default: null)
     * @return boolean
     */
    protected function validationRegex(Context $c, string $pattern, string $selector = null) : bool
    {
        return static::handleRegex($c, $pattern, 'validation.Regex', ['pattern' => $pattern], $selector);
    }

    /**
     * Handle Listable Value Type Validation
     * If you use this handler then you have to define @List message key too.
     *
     * @param Context $c
     * @param callable $test function($value) { ... }
     * @param string $messsage_key
     * @param array $replacement (default: [])
     * @param callable $selector function($value) { ... } (default: null)
     * @return boolean
     */
    public static function handleListableValue(Context $c, callable $test, string $messsage_key, array $replacement = [], callable $selector = null) : bool
    {
        if ($c->blank()) {
            return true;
        }
        $valid = true;
        foreach ((array)$c->value as $i => $value) {
            if (!$test($value)) {
                $replacement['nth']   = $c->ordinalize($i + 1);
                $replacement['value'] = $value;
                $c->appendError($messsage_key.(is_array($c->value) ? '@List' : ''), $replacement, $selector ? $selector($value) : null);
                $valid = false;
            }
        }
        return $valid;
    }

    /**
     * Handle Regex Type Validation
     * If you use this handler then you have to define @List message key too.
     *
     * @param Context $c
     * @param string $pattern
     * @param string $messsage_key
     * @param array $replacement (default: [])
     * @param int|string $selector (default: null)
     * @return boolean
     */
    public static function handleRegex(Context $c, string $pattern, string $messsage_key, array $replacement = [], $selector = null) : bool
    {
        return static::handleListableValue(
            $c,
            function ($value) use ($pattern) {
                return preg_match($pattern, $value);
            },
            $messsage_key,
            $replacement,
            function ($value) use ($selector) {
                return $selector;
            }
        );
    }
    
    /**
     * Not Regex Validation
     *
     * @param Context $c
     * @param string $pattern
     * @param string $selector (default: null)
     * @return boolean
     */
    protected function validationNotRegex(Context $c, string $pattern, string $selector = null) : bool
    {
        return static::handleNotRegex($c, $pattern, 'validation.NotRegex', ['pattern' => $pattern], $selector);
    }

    /**
     * Handle Not Regex type validation
     * If you use this handler then you have to define @List message key too.
     *
     * @param Context $c
     * @param string $pattern
     * @param string $messsage_key
     * @param array $replacement (default: [])
     * @param int|string $selector (default: null)
     * @return boolean
     */
    public static function handleNotRegex(Context $c, string $pattern, string $messsage_key, array $replacement = [], $selector = null) : bool
    {
        return static::handleListableValue(
            $c,
            function ($value) use ($pattern) {
                return !preg_match($pattern, $value);
            },
            $messsage_key,
            $replacement,
            function ($value) use ($selector) {
                return $selector;
            }
        );
    }
    
    /**
     * Max Length Validation
     *
     * @param Context $c
     * @param integer $max
     * @return boolean
     */
    public function validationMaxLength(Context $c, int $max) : bool
    {
        return static::handleListableValue(
            $c,
            function ($value) use ($max) {
                return mb_strlen($value) <= $max;
            },
            'validation.MaxLength',
            ['max' => $max]
        );
    }

    /**
     * Min Length Validation
     *
     * @param Context $c
     * @param integer $min
     * @return boolean
     */
    public function validationMinLength(Context $c, int $min) : bool
    {
        return static::handleListableValue(
            $c,
            function ($value) use ($min) {
                return mb_strlen($value) >= $min;
            },
            'validation.MinLength',
            ['min' => $min]
        );
    }

    /**
     * Length Validation
     *
     * @param Context $c
     * @param integer $length
     * @return boolean
     */
    public function validationLength(Context $c, int $length) : bool
    {
        return static::handleListableValue(
            $c,
            function ($value) use ($length) {
                return mb_strlen($value) === $length;
            },
            'validation.Length',
            ['length' => $length]
        );
    }

    /**
     * Numeric Validation
     *
     * @param Context $c
     * @return boolean
     */
    public function validationNumeric(Context $c) : bool
    {
        return static::handleListableValue($c, 'is_numeric', 'validation.Numeric');
    }

    // ====================================================
    // Built-in Condition Methods
    // ====================================================
    /**
     * If condition
     *
     * @param Context $c
     * @param string $other
     * @param mixed $value value or array :field_name
     * @return boolean
     */
    protected function validationIf(Context $c, string $other, $value) : bool
    {
        [$value, ] = $c->resolve($value);
        return in_array($c->value($other), is_null($value) ? [null] : (array)$value);
    }

    /**
     * Unless condition
     *
     * @param Context $c
     * @param string $other
     * @param mixed $value value or array or @field_name
     * @return boolean
     */
    protected function validationUnless(Context $c, string $other, $value) : bool
    {
        [$value, ] = $c->resolve($value);
        return !in_array($c->value($other), is_null($value) ? [null] : (array)$value);
    }
}
