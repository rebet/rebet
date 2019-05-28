<?php
namespace Rebet\Http;

use Rebet\Common\Strings;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;

/**
 * Uploaded File Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class UploadedFile extends SymfonyUploadedFile
{
    /**
     * @var int|null width when the uploaded file was image.
     */
    protected $width = null;

    /**
     * @var int|null height when the uploaded file was image.
     */
    protected $height = null;

    /**
     * {@inheritDoc}
     */
    public function __construct(string $path, string $original_name, ?string $mime_type = null, ?int $error = null, bool $test = false)
    {
        parent::__construct($path, $original_name, $mime_type, $error, $test);
        if (Strings::startsWith($this->getMimeType(), 'image/')) {
            try {
                $imagesize    = getimagesize($this->getRealPath());
                $this->width  = $imagesize[0] ?? null ;
                $this->height = $imagesize[1] ?? null ;
            } catch (ErrorException $e) {
                $this->width  = null ;
                $this->height = null ;
            }
        }
    }

    /**
     * Get the uploaded file width when the file was image.
     *
     * @return int|null
     */
    public function getWidth() : ?int
    {
        return $this->width;
    }

    /**
     * Get the uploaded file height when the file was image.
     *
     * @return int|null
     */
    public function getHeight() : ?int
    {
        return $this->height;
    }

    /**
     * It check the uploaded file has area (width and height).
     *
     * @return boolean
     */
    public function hasArea() : bool
    {
        return $this->width !== null && $this->height !== null;
    }

    /**
     * Convert to UploadedFile from given value.
     *
     * @param mixed $value
     * @return self|null
     */
    public static function valueOf($value) : ?self
    {
        switch (true) {
            case $value === null:
                return null;
            case $value instanceof static:
                return $value;
            case $value instanceof SymfonyUploadedFile:
                return new static(
                    $value->getPathname(),
                    $value->getClientOriginalName(),
                    $value->getClientMimeType(),
                    $value->getError()
                );
            case is_array($value):
                return new static(
                    $value['tmp_name'],
                    $value['name'],
                    $value['type'],
                    $value['error']
                );
        }
        return null;
    }
}
