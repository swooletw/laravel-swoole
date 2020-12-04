<?php

namespace SwooleTW\Http\Exceptions;

/**
 * Class FrameworkNotSupportException
 */
class FrameworkNotSupportException extends \RuntimeException
{
    /**
     * FrameworkNotSupportException constructor.
     *
     * @param string $framework
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(string $framework, int $code = 0, \Throwable $previous = null)
    {
        parent::__construct("Not support framework '{$framework}'", $code, $previous);
    }
}