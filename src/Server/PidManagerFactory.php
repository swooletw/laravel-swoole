<?php

namespace SwooleTW\Http\Server;

use Illuminate\Support\Facades\Config;

class PidManagerFactory
{
	public function __invoke() : PidManager
	{
		return new PidManager(
			Config::get('swoole_http.server.options.pid_file') ?? sys_get_temp_dir() . '/swoole.pid'
		);
	}
}
