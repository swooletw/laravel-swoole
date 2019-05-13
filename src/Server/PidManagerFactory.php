<?php

namespace SwooleTW\Http\Server;

use Psr\Container\ContainerInterface;

class PidManagerFactory
{
	public function __invoke(ContainerInterface $container) : PidManager
	{
		$config = $container->get('config');

		return new PidManager(
			$config->get('swoole_http.server.options.pid_file') ?? sys_get_temp_dir() . '/ofcold-swoole.pid'
		);
	}
}
