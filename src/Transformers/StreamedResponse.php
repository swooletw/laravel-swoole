<?php

namespace SwooleTW\Http\Transformers;

use Symfony\Component\HttpFoundation\StreamedResponse as BaseStreamedResponse;

/**
 * Class StreamedResponse
 */
class StreamedResponse extends BaseStreamedResponse
{
    /**
     * Output buffer
     *
     * @var string
     */
    protected $output;

    /**
     * Get output buffer
     *
     * @return string
     */
    public function getOutput(): ?string
    {
        return $this->output;
    }

    /**
     * Set output buffer
     *
     * @param string $output
     */
    public function setOutput(?string $output = null): void
    {
        $this->output = $output;
    }
}