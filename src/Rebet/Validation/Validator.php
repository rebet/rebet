<?php
namespace Rebet\Validation;

use Rebet\Common\Arrays;
use Rebet\Common\Exception\LogicException;
use Rebet\Common\Reflector;
use Rebet\Common\Strings;
use Rebet\Config\Configurable;

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
            'validations'                  => BuiltinValidations::class,
            'nested_attribute_auto_format' => true,
        ];
    }

    /**
     * Validations
     *
     * @var Validations
     */
    protected $validations;

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
     * Set nested attribute label auto format or not.
     *
     * @param boolean $nested_attribute_auto_format
     * @return void
     */
    public static function setNestedAttributeAutoFormat(bool $nested_attribute_auto_format) : void
    {
        static::setConfig(['nested_attribute_auto_format' => $nested_attribute_auto_format]);
    }

    /**
     * Create a new Validator instance.
     *
     * @param array $data
     * @param Validations $validations (default: depend on configure)
     */
    public function __construct(array $data, Validations $validations = null)
    {
        $this->data        = $data;
        $this->validations = $validations ?? static::configInstantiate('validations');
    }

    /**
     * Validate the data by given crud type rules.
     *
     * @todo Need to consider whether an argument should contains $nested_attribute_auto_format.
     *
     * @param string $crud
     * @param array|array[]|string|string[]|Rule|Rule[] $rules array(=map) of rule, string of Rule class name, Rule class instance and those lists.
     * @param bool $accept_undefined (default: false)
     * @return ValidData|null
     */
    public function validate(string $crud, $rules, bool $accept_undefined = false) : ?ValidData
    {
        if (!Arrays::isSequential($rules)) {
            $rules = [$rules];
        }

        $valid_data                   = new ValidData();
        $nested_attribute_auto_format = null;
        foreach ($rules as $rule) {
            $rule = is_string($rule) ? Reflector::instantiate($rule) : $rule ;
            $spot = null;
            if ($rule instanceof Rule) {
                $nested_attribute_auto_format = $nested_attribute_auto_format ?? $rule->nestedAttributeAutoFormat();
                $spot                         = $rule;
                $rule                         = $rule->rules();
            }

            $errors       = [];
            $context      = new Context($crud, $this->data, $errors, $rule, $nested_attribute_auto_format ?? static::config('nested_attribute_auto_format'));
            $valid_data   = Arrays::override($valid_data, $this->_validate($context, $rule, $spot));
            $this->errors = array_merge($this->errors, $errors);
        }

        foreach ($this->errors as &$messages) {
            $messages = array_values(array_unique($messages));
        }

        if (!empty($this->errors)) {
            return null;
        }

        if ($accept_undefined) {
            foreach ($this->data as $key => $value) {
                if (!$valid_data->has($key)) {
                    $valid_data[$key] = $value;
                }
            }
        }

        return $valid_data;
    }

    /**
     * Validate the data by given context and rules for recursive.
     *
     * @param Context $context
     * @param array $rules
     * @param Rule|null $spot_validations
     * @return ValidData
     */
    protected function _validate(Context $context, $rules, ?Rule $spot_validations) : ValidData
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
            $this->validateRules($context, $config['rule'] ?? [], $spot_validations);
            $data  = null;
            $nest  = $config['nest'] ?? [] ;
            $nests = $config['nests'] ?? [] ;
            if ($nest) {
                $data = $this->_validate($context->nest(), $nest, $spot_validations);
            } elseif ($nests) {
                $data = [];
                foreach (array_keys($context->value) as $key) {
                    $data[$key] = $this->_validate($context->nest($key), $nests, $spot_validations);
                }
                $data = new ValidData($data);
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
                    $context->appendError('ConvertFailed', ['convert' => $convert]);
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
     * @param Rule|null $spot_validations
     */
    protected function validateRules(Context $context, array $rules, ?Rule $spot_validations) : void
    {
        foreach ($rules as $rule) {
            if (!is_array($rule)) {
                throw new LogicException("Invalid rules format. A 'rule/then/else' list item should be array.");
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

            [$name, $option] = Strings::split($validation, ':', 2, '');
            if (Strings::contains($option, '?')) {
                $context->quiet(true);
            }
            $valid = false;
            if ($spot_validations && $spot_validations->hasCustomValidation($name)) {
                $valid = $spot_validations->validate($name, $context, ...$args);
            } else {
                $valid = $this->validations->validate($name, $context, ...$args);
            }
            if (!$valid && !$context->isQuiet() && Strings::contains($option, '!')) {
                return;
            }
            $context->quiet(false);

            $then = $rule['then'] ?? null;
            $else = $rule['else'] ?? null;
            if ($valid && $then) {
                $this->validateRules($context, $then, $spot_validations);
            }
            if (!$valid && $else) {
                $this->validateRules($context, $else, $spot_validations);
            }
        }
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
}
