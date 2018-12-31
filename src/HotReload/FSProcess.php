<?php

namespace SwooleTW\Http\HotReload;

use Swoole\Process as SwooleProcess;
use Symfony\Component\Process\Process as AppProcess;

/**
 * Class FSProcess
 *
 * @codeCoverageIgnore
 */
class FSProcess
{
    /**
     * Observed files.
     *
     * @var array
     */
    protected $filter;

    /**
     * Watch recursively.
     *
     * @var string
     */
    protected $recursively;

    /**
     * Watch directory.
     *
     * @var string
     */
    protected $directory;

    /**
     * When locked event cannot do anything.
     *
     * @var bool
     */
    protected $locked;

    /**
     * FSProcess constructor.
     *
     * @param string $filter
     * @param string $recursively
     * @param string $directory
     */
    public function __construct(string $filter, string $recursively, string $directory)
    {
        $this->filter = $filter;
        $this->recursively = $recursively;
        $this->directory = $directory;
        $this->locked = false;
    }

    /**
     * Make swoole process.
     *
     * @param callable|null $callback
     *
     * @return \Swoole\Process
     */
    public function make(?callable $callback = null)
    {
        $mcb = function ($type, $buffer) use ($callback) {
            if (AppProcess::OUT === $type && $event = FSEventParser::toEvent($buffer)) {
                $this->locked = true;
                ($callback) ? $callback($event) : null;
                $this->locked = false;
                unset($event);
            }
        };

        return new SwooleProcess(function () use ($mcb) {
            (new AppProcess($this->configure()))->setTimeout(0)->run($mcb);
        }, false, false);
    }

    /**
     * Configure process.
     *
     * @return array
     */
    protected function configure(): array
    {
        return [
            'fswatch',
            $this->recursively ? '-rtx' : '-tx',
            '-e',
            '.*',
            '-i',
            "\\{$this->filter}$",
            $this->directory,
        ];
    }
}
