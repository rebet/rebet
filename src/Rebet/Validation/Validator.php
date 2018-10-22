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
     * @return Collection|null
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
        foreach ($rules as $rule) {
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
            $valid           = false;
            if ($custom_validator && $custom_validator->hasCustomValidation($name)) {
                $valid = $custom_validator->validate($name, $context, ...$args);
            } else {
                $global_validator = static::config("validation.{$name}", false, null);
                $method = "validate{$name}";
                $valid  = $global_validator ? call_user_func($global_validator, $context, ...$args) : $this->$method($context, ...$args) ;
            }
    
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
     * @param callable $validation
     * @return void
     */
    public static function addValidation(string $name, callable $validation) : void
    {
        static::setConfig(['validation' => [$name => $validation]]);
    }
}
