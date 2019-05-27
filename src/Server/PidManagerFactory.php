<?php

namespace SwooleTW\Http\Server;

class PidManagerFactory
{
	public function __invoke() : PidManager
	{
		return new PidManager(
			config('swoole_http.server.options.pid_file') ?? sys_get_temp_dir() . '/swoole.pid'
		);
	}
}
