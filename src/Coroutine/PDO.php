<?php

/**
 * Copyright: Swlib
 * Author: Twosee <twose@qq.com>
 * Modifier: Albert Chen
 * License: Apache 2.0
 */

namespace SwooleTW\Http\Coroutine;


use Exception;
use Illuminate\Database\QueryException;
use PDO as BasePDO;

class PDO extends BasePDO
{
    public static $keyMap = [
        'dbname' => 'database',
    ];

    private static $options = [
        'host' => '',
        'port' => 3306,
        'user' => '',
        'password' => '',
        'database' => '',
        'charset' => 'utf8mb4',
        'strict_type' => true,
    ];

    /** @var \Swoole\Coroutine\Mysql */
    public $client;

    public $inTransaction = false;

    /**
     * PDO constructor.
     *
     * @param string $dsn
     * @param string $username
     * @param string $password
     * @param array $options
     *
     * @throws \SwooleTW\Http\Coroutine\ConnectionException
     */
    public function __construct(string $dsn, string $username = '', string $password = '', array $options = [])
    {
        parent::__construct($dsn, $username, $password, $options);
        $this->setClient();
        $this->connect($this->getOptions(...func_get_args()));
    }

    /**
     * @param null $client
     * @param null $type
     */
    protected function setClient($client = null, $type = null)
    {
        $this->client = $client ?: new \Swoole\Coroutine\Mysql($type);
    }

    /**
     * @param array $options
     *
     * @return $this
     * @throws \SwooleTW\Http\Coroutine\ConnectionException
     */
    protected function connect(array $options = [])
    {
        $this->client->connect($options);

        if (!$this->client->connected) {
            $message = $this->client->connect_error ?: $this->client->error;
            $errorCode = $this->client->connect_errno ?: $this->client->errno;

            throw new ConnectionException($message, $errorCode);
        }

        return $this;
    }

    /**
     * @param $dsn
     * @param $username
     * @param $password
     * @param $driverOptions
     *
     * @return array
     */
    protected function getOptions($dsn, $username, $password, $driverOptions)
    {
        $dsn = explode(':', $dsn);
        $driver = ucwords(array_shift($dsn));
        $dsn = explode(';', implode(':', $dsn));
        $options = [];

        static::checkDriver($driver);

        foreach ($dsn as $kv) {
            $kv = explode('=', $kv);
            if ($kv) {
                $options[$kv[0]] = $kv[1] ?? '';
            }
        }

        $authorization = [
            'user' => $username,
            'password' => $password,
        ];

        $options = $driverOptions + $authorization + $options;

        foreach (static::$keyMap as $pdoKey => $swpdoKey) {
            if (isset($options[$pdoKey])) {
                $options[$swpdoKey] = $options[$pdoKey];
                unset($options[$pdoKey]);
            }
        }

        return $options + static::$options;
    }

    /**
     * @param string $driver
     */
    public static function checkDriver(string $driver)
    {
        if (!in_array($driver, static::getAvailableDrivers())) {
            throw new \InvalidArgumentException("{$driver} driver is not supported yet.");
        }
    }

    /**
     * @return array
     */
    public static function getAvailableDrivers()
    {
        return ['Mysql'];
    }

    /**
     * @return bool|void
     */
    public function beginTransaction()
    {
        $this->client->begin();
        $this->inTransaction = true;
    }

    /**
     * @return bool|void
     */
    public function rollBack()
    {
        $this->client->rollback();
        $this->inTransaction = false;
    }

    /**
     * @return bool|void
     */
    public function commit()
    {
        $this->client->commit();
        $this->inTransaction = true;
    }

    /**
     * @return bool
     */
    public function inTransaction()
    {
        return $this->inTransaction;
    }

    /**
     * @param null $seqname
     *
     * @return int|string
     */
    public function lastInsertId($seqname = null)
    {
        return $this->client->insert_id;
    }

    /**
     * @return mixed|void
     */
    public function errorCode()
    {
        $this->client->errno;
    }

    /**
     * @return array
     */
    public function errorInfo()
    {
        return [
            $this->client->errno,
            $this->client->errno,
            $this->client->error,
        ];
    }

    /**
     * @param string $statement
     *
     * @return int
     */
    public function exec($statement): int
    {
        $this->query($statement);

        return $this->client->affected_rows;
    }

    /**
     * @param string $statement
     * @param float $timeout
     *
     * @return array|bool|false|\PDOStatement
     */
    public function query(string $statement, float $timeout = -1)
    {
        $result = $this->client->query($statement, $timeout);

        if ($result === false) {
            $exception = new Exception($this->client->error, $this->client->errno);
            throw new QueryException($statement, [], $exception);
        }

        return $result;
    }

    /**
     * @param string $statement
     * @param array $options
     *
     * @return bool|\PDOStatement|\SwooleTW\Http\Coroutine\PDOStatement
     */
    public function prepare($statement, $options = null)
    {
        $options = is_null($options) ? [] : $options;
        if (strpos($statement, ':') !== false) {
            $i = 0;
            $bindKeyMap = [];
            $statement = preg_replace_callback('/:(\w+)\b/', function ($matches) use (&$i, &$bindKeyMap) {
                $bindKeyMap[$matches[1]] = $i++;

                return '?';
            }, $statement);
        }

        $stmtObj = $this->client->prepare($statement);

        if ($stmtObj) {
            $stmtObj->bindKeyMap = $bindKeyMap ?? [];

            return new PDOStatement($this, $stmtObj, $options);
        } else {
            $statementException = new StatementException($this->client->error, $this->client->errno);
            throw new QueryException($statement, [], $statementException);
        }
    }

    /**
     * @param int $attribute
     *
     * @return bool|mixed|string
     */
    public function getAttribute($attribute)
    {
        switch ($attribute) {
            case \PDO::ATTR_AUTOCOMMIT:
                return true;
            case \PDO::ATTR_CASE:
            case \PDO::ATTR_CLIENT_VERSION:
            case \PDO::ATTR_CONNECTION_STATUS:
                return $this->client->connected;
            case \PDO::ATTR_DRIVER_NAME:
            case \PDO::ATTR_ERRMODE:
                return 'Swoole Style';
            case \PDO::ATTR_ORACLE_NULLS:
            case \PDO::ATTR_PERSISTENT:
            case \PDO::ATTR_PREFETCH:
            case \PDO::ATTR_SERVER_INFO:
                // TODO Maybe dead code
//                return $this->serverInfo['timeout'] ?? self::$options['timeout'];
                return self::$options['timeout'];
            case \PDO::ATTR_SERVER_VERSION:
                return 'Swoole Mysql';
            case \PDO::ATTR_TIMEOUT:
            default:
                throw new \InvalidArgumentException('Not implemented yet!');
        }
    }

    /**
     * @param string $string
     * @param null $paramtype
     *
     * @return string|void
     */
    public function quote($string, $paramtype = null)
    {
        throw new \BadMethodCallException(<<<TXT
If you are using this function to build SQL statements,
you are strongly recommended to use PDO::prepare() to prepare SQL statements
with bound parameters instead of using PDO::quote() to interpolate user input into an SQL statement.
Prepared statements with bound parameters are not only more portable, more convenient,
immune to SQL injection, but are often much faster to execute than interpolated queries,
as both the server and client side can cache a compiled form of the query.
TXT
        );
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        $this->client->close();
    }
}
