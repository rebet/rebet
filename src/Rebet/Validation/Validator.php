<?php
namespace Rebet\Validation;

use Rebet\Config\Configurable;
use Rebet\File\Files;
use Rebet\Config\Config;
use Rebet\Config\LocaleResource;
use Rebet\Translation\Translator;
use Rebet\Translation\FileLoader;
use Rebet\Common\Collection;

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
     * Undocumented function
     *
     * @param string $crud
     * @param array|Rule $rules
     * @return Collection|null
     */
    public function validate(string $crud, $rules) : ?Collection
    {
        //todo 実装
    }
}
