<?php

namespace SwooleTW\Http\Server;

class PidManager
{
    protected $pidFile;

    public function __construct(string $pidFile = null)
    {
        $this->setPidFile(
            $pidFile ?: sys_get_temp_dir() . '/swoole.pid'
        );
    }

    /**
     * Set pid file path
     */
    public function setPidFile(string $pidFile): self
    {
        $this->pidFile = $pidFile;

        return $this;
    }

    /**
     * Write master pid and manager pid to pid file
     *
     * @throws \RuntimeException when $pidFile is not writable
     */
    public function write(int $masterPid, int $managerPid): void
    {
        if (! is_writable($this->pidFile)
            && ! is_writable(dirname($this->pidFile))
        ) {
            throw new \RuntimeException(
                sprintf('Pid file "%s" is not writable', $this->pidFile)
            );
        }

        file_put_contents($this->pidFile, $masterPid . ',' . $managerPid);
    }

    /**
     * Read master pid and manager pid from pid file
     *
     * @return string[] {
     *   @var string $masterPid
     *   @var string $managerPid
     * }
     */
    public function read(): array
    {
        $pids = [];

        if (is_readable($this->pidFile)) {
            $content = file_get_contents($this->pidFile);
            $pids = explode(',', $content);

            // when upgraded from an old version with a running swoole:http
            // the pid file only contains one pid number while the newer version requires two
            // then the swoole:http command would encounter a bug which the array index 1 is inaccessible
            if (count($pids) !== 2) {
                $pids = [0, 0];
            }
        }

        return $pids;
    }

    /**
     * Gets pid file path.
     *
     * @return string
     */
    public function file()
    {
        return $this->pidFile;
    }

    /**
     * Delete pid file
     */
    public function delete(): bool
    {
        if (is_writable($this->pidFile)) {
            return unlink($this->pidFile);
        }

        return false;
    }
}
