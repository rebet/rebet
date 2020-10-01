<?php
namespace Rebet\Http;

use Rebet\Tools\Utility\Strings;
use Rebet\Filesystem\Filesystem;
use Rebet\Filesystem\Storage;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;
use Symfony\Component\Mime\MimeTypes;

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
            } catch (\ErrorException $e) {
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

    /**
     * Store the uploaded file on a filesystem disk.
     * This method can be contains {.ext} placeholder in $path argument, like below
     *
     *  $path = $uploaded_file->store("users/{$user_id}/avatar{.ext}");
     *
     * The {.ext} placeholder will be replaced by guessed extension from mime type.
     * If can not guess extension by mime type then use this uploaded file extension as it is.
     *
     * @param string $path can be contains {.ext} placeholder.
     * @param string|array $options (default: [])
     * @param string|null $disk name (default: null for private disk)
     * @return string of saved path
     */
    public function store(string $path, $options = [], ?string $disk = null) : string
    {
        $options    = is_string($options) ? ['visibility' => $options] : (array) $options ;
        $filesystem = $disk ? Storage::disk($disk) : Storage::private() ;
        return $filesystem->put($path, $this, array_merge(['.ext' => $this->guessExtension()], $options));
    }

    /**
     * Returns the extension based on the mime type.
     * If the mime type is unknown, returns null.
     * If the mime type cannot be narrowed down to one extension, then this uploaded files extension will be returned when the extension was contained in candidate extensios.
     * Otherwise returns null.
     *
     * This method uses the mime type as guessed by getMimeType() to guess the file extension.
     *
     * @return string|null The guessed extension or null if it cannot be guessed
     *
     * @see MimeTypes
     * @see getMimeType()
     */
    public function guessExtension()
    {
        $candidate_extensions = MimeTypes::getDefault()->getExtensions($this->getMimeType());
        if (count($candidate_extensions) === 1) {
            return $candidate_extensions[0];
        }

        $extension = strtolower($this->getExtension());
        if (in_array($extension, $candidate_extensions, true)) {
            return $extension;
        }

        return null;
    }
}
