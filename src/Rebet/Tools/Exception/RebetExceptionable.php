<?php
declare(strict_types=1);

namespace Rebet\Tools\Exception;

/**
 * Rebet Exceptionable Trait
 *
 * @see RebetException
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
trait RebetExceptionable
{
    /**
     * @var mixed appendix data.
     */
    protected $appendix;

    /**
     * {@inheritDoc}
     */
    public function caused(\Throwable $previous) : static
    {
        $rc = new \ReflectionClass(\Exception::class);
        $rp = $rc->getProperty('previous');
        $rp->setValue($this, $previous);
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getCaused() : \Throwable|null
    {
        $rc = new \ReflectionClass(\Exception::class);
        $rp = $rc->getProperty('previous');
        return $rp->getValue($this);
    }

    /**
     * {@inheritDoc}
     */
    public function code($code) : static
    {
        $this->code = $code;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function appendix($appendix) : static
    {
        $this->appendix = $appendix;
        return $this;
    }

    /**
     * Get appendix data.
     *
     * @return mixed
     */
    public function getAppendix()
    {
        return $this->appendix;
    }

    /**
     * {@inheritDoc}
     */
    public function __toString()
    {
        $string = parent::__toString();
        if ($this->appendix) {
            $appendix = json_encode($this->appendix);
            $appendix = json_last_error() === JSON_ERROR_NONE ? $appendix : '(Can not stringize)' ;
            $string   = preg_replace('/^Stack trace:$/mu', "Appendix:\n{$appendix}\nStack trace:", $string, 1);
        }
        return $string;
    }
}
