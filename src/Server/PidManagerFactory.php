<?php

namespace SwooleTW\Http\Server;

use Psr\Container\ContainerInterface;
use Illuminate\Contracts\Config\Repository as ConfigContract;

class PidManagerFactory
{
	public function __invoke(ContainerInterface $container): PidManager
	{
        $config = $container->get('config');

		return new PidManager(
			$config->get('swoole_http.server.options.pid_file') ?: sys_get_temp_dir() . '/swoole.pid'
		);
	}
}
