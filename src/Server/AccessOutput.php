<?php

namespace SwooleTW\Http\Server;

use DateTimeInterface;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Class AccessLog
 *
 * @codeCoverageIgnore
 */
class AccessOutput
{
    /**
     * @var \Illuminate\Console\OutputStyle
     */
    protected $output;

    /**
     * AccessOutput constructor.
     *
     * @param \Symfony\Component\Console\Output\ConsoleOutput $output
     */
    public function __construct(ConsoleOutput $output)
    {
        $this->output = $output;
    }

    /**
     * Access log.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Symfony\Component\HttpFoundation\Response $response
     */
    public function log(Request $request, Response $response): void
    {
        $host = $request->url();
        $method = $request->method();
        $agent = $request->userAgent();
        $date = $this->date($response->getDate());
        $status = $response->getStatusCode();
        $style = $this->style($status);

        $this->output->writeln(
            sprintf("%s %s %s <$style>%d</$style> %s", $host, $date, $method, $status, $agent)
        );
    }

    /**
     * @param \DateTimeInterface $date
     *
     * @return string
     */
    protected function date(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }

    /**
     * @param int $status
     *
     * @return string
     */
    protected function style(int $status): string
    {
        return $status !== Response::HTTP_OK ? 'error' : 'info';
    }
}
