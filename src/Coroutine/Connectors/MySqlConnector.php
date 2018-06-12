<?php

namespace SwooleTW\Http\Coroutine\Connectors;

use SwooleTW\Http\Coroutine\PDO as SwoolePDO;
use Illuminate\Database\Connectors\MySqlConnector as BaseConnector;

class MySqlConnector extends BaseConnector
{
    /**
     * Establish a database connection.
     *
     * @param  array  $config
     * @return \PDO
     */
    public function connect(array $config)
    {
        list($username, $password) = [
            $config['username'] ?? null, $config['password'] ?? null,
        ];
        $dsn = $this->getDsn($config);
        $options = $this->getOptions($config);

        // We need to grab the PDO options that should be used while making the brand
        // new connection instance. The PDO options control various aspects of the
        // connection's behavior, and some might be specified by the developers.
        $connection = new SwoolePDO($dsn, $username, $password, $options);

        if (! empty($config['database'])) {
            $connection->exec("use `{$config['database']}`;");
        }

        $this->configureEncoding($connection, $config);

        // Next, we will check to see if a timezone has been specified in this config
        // and if it has we will issue a statement to modify the timezone with the
        // database. Setting this DB timezone is an optional configuration item.
        $this->configureTimezone($connection, $config);

        $this->setModes($connection, $config);

        return $connection;
    }
}
