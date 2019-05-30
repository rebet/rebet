<?php
namespace Rebet\Validation;

/**
 * Validate Rule Abstract Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
abstract class Rule
{
    /**
     * Get the string contents of the view.
     *
     * @param string $name Template name without base template dir and template file suffix
     * @param array $data
     * @return string
     */
    abstract public function rules() : array;

    /**
     * Check the rule has custom validation of given name.
     *
     * @param string $name
     * @return bool
     */
    public function hasCustomValidation(string $name) : bool
    {
        return method_exists($this, "validation{$name}");
    }

    /**
     * Invoke the custom validation of given name.
     *
     * @param string $name
     * @param Context $context
     * @param mixed ...$args
     * @return bool
     */
    public function validate(string $name, Context $context, ...$args) : bool
    {
        $method = "validation{$name}";
        return $this->$method($context, ...$args);
    }

    /**
     * Get the nested attribute auto format setting for this rule.
     * This method always return 'null' for using configure setting, so if you need to control auto format each by rule then override the method.
     *
     * @return bool|null
     */
    public function nestedAttributeAutoFormat() : ?bool
    {
        return null;
    }
}
