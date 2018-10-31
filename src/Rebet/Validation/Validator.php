<?php
namespace Rebet\Validation;

use Rebet\Config\Configurable;
use Rebet\Translation\Translator;
use Rebet\Common\Collection;
use Rebet\Common\Reflector;
use Rebet\Common\Strings;

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
            'validations' => BuiltinValidations::class,
        ];
    }

    /**
     * Validations
     *
     * @var Validations
     */
    protected $validations;

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
     * @param Validations $validations (default: depend on configure)
     * @param Translator $translator (default: $validations->translator())
     */
    public function __construct(array $data, Validations $validations = null, Translator $translator = null)
    {
        $this->data        = $data;
        $this->validations = $validations ?? static::configInstantiate('validations');
        $this->translator  = $translator  ?? $this->validations->translator() ;
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
        $spot_validations = null;
        if ($rules instanceof Rule) {
            $spot_validations = $rules;
            $rules            = $rules->rules();
        }
        
        $context    = new Context($crud, $this->data, $this->errors, $rules, $this->translator);
        $valid_data = $this->_validate($context, $rules, $spot_validations);
        return $context->hasError('*') ? null : $valid_data ;
    }

    /**
     * Validate the data by given context and rules for recursive.
     *
     * @param Context $context
     * @param array $rules
     * @param Rule|null $spot_validations
     * @return Collection|null
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
            $nest  = $config['nest']  ?? [] ;
            $nests = $config['nests'] ?? [] ;
            if ($nest) {
                $data = $this->_validate($context->nest(), $nest, $spot_validations);
            } elseif ($nests) {
                $data = [];
                foreach (array_keys($context->value) as $key) {
                    $data[$key] = $this->_validate($context->nest($key), $nests, $spot_validations);
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
     * @param Rule|null $spot_validations
     */
    protected function validateRules(Context $context, array $rules, ?Rule $spot_validations) : void
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
