<?php
namespace Rebet\Validation;

/**
 * Validate Rule Interface
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
interface ValidationRule
{
    /**
     * Get the string contents of the view.
     *
     * @param string $name Template name without base template dir and template file suffix
     * @param array $data
     * @return string
     */
    public static function rules() : array;
}
