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
use Rebet\Common\Utils;
use Rebet\Common\System;

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
        return static::handleRegex($c, Kind::OTHER(), $pattern, 'validation.Regex', ['pattern' => $pattern], $selector);
    }

    /**
     * Handle Listable Value Type Validation
     * If you use this handler then you have to define @List message key too.
     *
     * @param Context $c
     * @param Kind $kind
     * @param callable $test function($value) { ... }
     * @param string $messsage_key
     * @param array $replacement (default: [])
     * @param callable $selector function($value) { ... } (default: null)
     * @return boolean
     */
    public static function handleListableValue(Context $c, Kind $kind, callable $test, string $messsage_key, array $replacement = [], callable $selector = null) : bool
    {
        if ($c->blank()) {
            return true;
        }
        $valid         = true;
        $error_indices = $c->extra('error_indices') ?? [];
        foreach ((array)$c->value as $i => $value) {
            if (!$c->isQuiet() && !$kind->equals(Kind::OTHER()) && $error_indices[$i] ?? false) {
                continue;
            }
            if (!$test($value)) {
                $replacement['nth']   = $c->ordinalize($i + 1);
                $replacement['value'] = $value;
                $c->appendError($messsage_key.(is_array($c->value) ? '@List' : ''), $replacement, $selector ? $selector($value) : null);
                $valid = false;
                if ($kind->equals(Kind::TYPE_CONSISTENCY_CHECK())) {
                    $error_indices[$i] = true;
                }
            }
        }
        $c->setExtra('error_indices', $error_indices);
        return $valid;
    }

    /**
     * Handle Regex Type Validation
     * If you use this handler then you have to define @List message key too.
     *
     * @param Context $c
     * @param Kind $kind
     * @param string $pattern
     * @param string $messsage_key
     * @param array $replacement (default: [])
     * @param int|string $selector (default: null)
     * @return boolean
     */
    public static function handleRegex(Context $c, Kind $kind, string $pattern, string $messsage_key, array $replacement = [], $selector = null) : bool
    {
        return static::handleListableValue(
            $c,
            $kind,
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
        return static::handleNotRegex($c, Kind::OTHER(), $pattern, 'validation.NotRegex', ['pattern' => $pattern], $selector);
    }

    /**
     * Handle Not Regex type validation
     * If you use this handler then you have to define @List message key too.
     *
     * @param Context $c
     * @param Kind $kind
     * @param string $pattern
     * @param string $messsage_key
     * @param array $replacement (default: [])
     * @param int|string $selector (default: null)
     * @return boolean
     */
    public static function handleNotRegex(Context $c, Kind $kind, string $pattern, string $messsage_key, array $replacement = [], $selector = null) : bool
    {
        return static::handleListableValue(
            $c,
            $kind,
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
            Kind::OTHER(),
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
            Kind::OTHER(),
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
            Kind::OTHER(),
            function ($value) use ($length) {
                return mb_strlen($value) === $length;
            },
            'validation.Length',
            ['length' => $length]
        );
    }

    /**
     * Number Validation
     *
     * @param Context $c
     * @return boolean
     */
    public function validationNumber(Context $c) : bool
    {
        return static::handleRegex($c, Kind::TYPE_CONSISTENCY_CHECK(), "/^[+-]?[0-9]*[\.]?[0-9]+$/u", 'validation.Number');
    }

    /**
     * Integer Validation
     *
     * @param Context $c
     * @return boolean
     */
    public function validationInteger(Context $c) : bool
    {
        return static::handleRegex($c, Kind::TYPE_CONSISTENCY_CHECK(), "/^[+-]?[0-9]+$/u", 'validation.Integer');
    }

    /**
     * Float Validation
     *
     * @param Context $c
     * @param int $decimal
     * @return boolean
     */
    public function validationFloat(Context $c, int $decimal) : bool
    {
        return static::handleRegex($c, Kind::TYPE_CONSISTENCY_CHECK(), "/^[+-]?[0-9]+([\.][0-9]{0,{$decimal}})?$/u", 'validation.Float', ['decimal' => $decimal]);
    }

    /**
     * Max Number Validation
     *
     * @param Context $c
     * @param int|float|string $max
     * @param int $decimal (default: 0)
     * @return boolean
     */
    public function validationMaxNumber(Context $c, $max, int $decimal = 0) : bool
    {
        if ($c->blank()) {
            return true;
        }
        
        $valid  = $decimal === 0 ? $this->validationInteger($c) : $this->validationFloat($c, $decimal) ;
        $valid &= static::handleListableValue(
            $c,
            Kind::TYPE_DEPENDENT_CHECK(),
            function ($value) use ($max, $decimal) {
                return bccomp((string)$value, (string)$max, $decimal) !== 1;
            },
            'validation.MaxNumber',
            ['max' => $max, 'decimal' => $decimal]
        );
        return $valid;
    }

    /**
     * Min Number Validation
     *
     * @param Context $c
     * @param int|float|string $min
     * @param int $decimal (default: 0)
     * @return boolean
     */
    public function validationMinNumber(Context $c, $min, int $decimal = 0) : bool
    {
        if ($c->blank()) {
            return true;
        }
        
        $valid  = $decimal === 0 ? $this->validationInteger($c) : $this->validationFloat($c, $decimal) ;
        $valid &= static::handleListableValue(
            $c,
            Kind::TYPE_DEPENDENT_CHECK(),
            function ($value) use ($min, $decimal) {
                return bccomp((string)$min, (string)$value, $decimal) !== 1;
            },
            'validation.MinNumber',
            ['min' => $min, 'decimal' => $decimal]
        );
        return $valid;
    }

    /**
     * Email Validation
     *
     * @param Context $c
     * @param bool $strict (default: true)
     * @return boolean
     */
    public function validationEmail(Context $c, bool $strict = true) : bool
    {
        if ($strict) {
            return static::handleListableValue(
                $c,
                Kind::TYPE_CONSISTENCY_CHECK(),
                function ($value) {
                    return filter_var($value, FILTER_VALIDATE_EMAIL);
                },
                'validation.Email'
            );
        }

        return $this->handleRegex($c, Kind::TYPE_CONSISTENCY_CHECK(), "/[A-Z0-9a-z._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,64}/", 'validation.Email');
    }

    /**
     * Url Validation
     *
     * @param Context $c
     * @param bool $dns_check (default: false)
     * @return boolean
     */
    public function validationUrl(Context $c, bool $dns_check = false) : bool
    {
        if ($c->blank()) {
            return true;
        }
        
        /*
         * This pattern is derived from Symfony\Component\Validator\Constraints\UrlValidator (2.7.4).
         *
         * (c) Fabien Potencier <fabien@symfony.com> http://symfony.com
         */
        $pattern = '~^
            ((aaa|aaas|about|acap|acct|acr|adiumxtra|afp|afs|aim|apt|attachment|aw|barion|beshare|bitcoin|blob|bolo|callto|cap|chrome|chrome-extension|cid|coap|coaps|com-eventbrite-attendee|content|crid|cvs|data|dav|dict|dlna-playcontainer|dlna-playsingle|dns|dntp|dtn|dvb|ed2k|example|facetime|fax|feed|feedready|file|filesystem|finger|fish|ftp|geo|gg|git|gizmoproject|go|gopher|gtalk|h323|ham|hcp|http|https|iax|icap|icon|im|imap|info|iotdisco|ipn|ipp|ipps|irc|irc6|ircs|iris|iris.beep|iris.lwz|iris.xpc|iris.xpcs|itms|jabber|jar|jms|keyparc|lastfm|ldap|ldaps|magnet|mailserver|mailto|maps|market|message|mid|mms|modem|ms-help|ms-settings|ms-settings-airplanemode|ms-settings-bluetooth|ms-settings-camera|ms-settings-cellular|ms-settings-cloudstorage|ms-settings-emailandaccounts|ms-settings-language|ms-settings-location|ms-settings-lock|ms-settings-nfctransactions|ms-settings-notifications|ms-settings-power|ms-settings-privacy|ms-settings-proximity|ms-settings-screenrotation|ms-settings-wifi|ms-settings-workplace|msnim|msrp|msrps|mtqp|mumble|mupdate|mvn|news|nfs|ni|nih|nntp|notes|oid|opaquelocktoken|pack|palm|paparazzi|pkcs11|platform|pop|pres|prospero|proxy|psyc|query|redis|rediss|reload|res|resource|rmi|rsync|rtmfp|rtmp|rtsp|rtsps|rtspu|secondlife|s3|service|session|sftp|sgn|shttp|sieve|sip|sips|skype|smb|sms|smtp|snews|snmp|soap.beep|soap.beeps|soldat|spotify|ssh|steam|stun|stuns|submit|svn|tag|teamspeak|tel|teliaeid|telnet|tftp|things|thismessage|tip|tn3270|turn|turns|tv|udp|unreal|urn|ut2004|vemmi|ventrilo|videotex|view-source|wais|webcal|ws|wss|wtai|wyciwyg|xcon|xcon-userid|xfire|xmlrpc\.beep|xmlrpc.beeps|xmpp|xri|ymsgr|z39\.50|z39\.50r|z39\.50s))://                                 # protocol
            (([\pL\pN-]+:)?([\pL\pN-]+)@)?          # basic auth
            (
                ([\pL\pN\pS\-\.])+(\.?([\pL]|xn\-\-[\pL\pN-]+)+\.?) # a domain name
                    |                                              # or
                \d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}                 # an IP address
                    |                                              # or
                \[
                    (?:(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){6})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:::(?:(?:(?:[0-9a-f]{1,4})):){5})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:[0-9a-f]{1,4})))?::(?:(?:(?:[0-9a-f]{1,4})):){4})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,1}(?:(?:[0-9a-f]{1,4})))?::(?:(?:(?:[0-9a-f]{1,4})):){3})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,2}(?:(?:[0-9a-f]{1,4})))?::(?:(?:(?:[0-9a-f]{1,4})):){2})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,3}(?:(?:[0-9a-f]{1,4})))?::(?:(?:[0-9a-f]{1,4})):)(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,4}(?:(?:[0-9a-f]{1,4})))?::)(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,5}(?:(?:[0-9a-f]{1,4})))?::)(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,6}(?:(?:[0-9a-f]{1,4})))?::))))
                \]  # an IPv6 address
            )
            (:[0-9]+)?                              # a port (optional)
            (/?|/\S+|\?\S*|\#\S*)                   # a /, nothing, a / with something, a query or a fragment
        $~ixu';
        $valid = $this->handleRegex($c, Kind::TYPE_CONSISTENCY_CHECK(), $pattern, 'validation.Url');
        if ($dns_check) {
            $host_state = [];
            $valid &= $this->handleListableValue(
                $c,
                Kind::TYPE_DEPENDENT_CHECK(),
                function ($value) use (&$host_state) {
                    $host = parse_url($value, PHP_URL_HOST);
                    if (isset($host_state[$host])) {
                        return $host_state[$host];
                    }
                    $active = $host ? count(System::dns_get_record($host, DNS_A | DNS_AAAA)) > 0 : false ;
                    $host_state[$host] = $active;
                    return $active;
                },
                'validation.Url',
                [],
                function ($value) {
                    return 'nonactive';
                }
            );
        }
        return $valid;
    }

    /**
     * IPv4 Validation
     *
     * @param Context $c
     * @param bool $delimiter (default: null)
     * @return boolean
     */
    public function validationIpv4(Context $c, string $delimiter = null) : bool
    {
        if (!is_null($delimiter) && is_string($c->value)) {
            $splited = [];
            foreach (explode($delimiter, $c->value) as $value) {
                $value = trim($value);
                if (!Utils::isBlank($value)) {
                    $splited[] = $value;
                }
            }
            $c->value = $splited;
        }
        return $this->handleRegex($c, Kind::TYPE_CONSISTENCY_CHECK(), "/^(([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5]).){3}([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])(\/([1-9]|[1-2][0-9]|3[0-2]))?$/u", 'validation.Ipv4');
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
