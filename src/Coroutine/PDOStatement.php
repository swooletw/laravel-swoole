<?php

/**
 * Copyright: Swlib
 * Author: Twosee <twose@qq.com>
 * Modifier: Albert Chen
 * License: Apache 2.0
 */

namespace SwooleTW\Http\Coroutine;

use PDOStatement as BaseStatement;
use Swoole\Coroutine\MySQL\Statement;

class PDOStatement extends BaseStatement
{
    private $parent;

    public $statement;

    public $timeout;

    public $bindMap = [];

    public $cursor = -1;

    public $cursorOrientation = PDO::FETCH_ORI_NEXT;

    public $resultSet = [];

    public $fetchStyle = PDO::FETCH_BOTH;

    public function __construct(PDO $parent, Statement $statement, array $driverOptions = [])
    {
        $this->parent = $parent;
        $this->statement = $statement;
        $this->timeout = $driverOptions['timeout'] ?? -1;
    }

    public function errorCode()
    {
        return $this->statement->errno;
    }

    public function errorInfo()
    {
        return $this->statement->error;
    }

    public function rowCount()
    {
        return $this->statement->affected_rows;
    }

    public function bindParam($parameter, &$variable, $type = null, $maxlen = null, $driverdata = null)
    {
        if (! is_string($parameter) && ! is_int($parameter)) {
            return false;
        }

        $parameter = ltrim($parameter, ':');
        $this->bindMap[$parameter] = &$variable;

        return true;
    }

    public function bindValue($parameter, $variable, $type = null)
    {
        if (! is_string($parameter) && ! is_int($parameter)) {
            return false;
        }

        if (is_object($variable)) {
            if (! method_exists($variable, '__toString')) {
                return false;
            } else {
                $variable = (string) $variable;
            }
        }

        $parameter = ltrim($parameter, ':');
        $this->bindMap[$parameter] = $variable;

        return true;
    }

    private function afterExecute()
    {
        $this->cursor = -1;
        $this->bindMap = [];
    }

    public function execute($inputParameters = null)
    {
        if (! empty($inputParameters)) {
            foreach ($inputParameters as $key => $value) {
                $this->bindParam($key, $value);
            }
        }

        $inputParameters = [];
        if (! empty($this->statement->bindKeyMap)) {
            foreach ($this->statement->bindKeyMap as $nameKey => $numKey) {
                if (isset($this->bindMap[$nameKey])) {
                    $inputParameters[$numKey] = $this->bindMap[$nameKey];
                }
            }
        } else {
            $inputParameters = $this->bindMap;
        }

        $result = $this->statement->execute($inputParameters, $this->timeout);
        $this->resultSet = ($ok = $result !== false) ? $result : [];
        $this->afterExecute();

        if ($result === false) {
            throw new \PDOException($this->errorInfo(), $this->errorCode());
        }

        return $ok;
    }

    public function setFetchMode($fetchStyle, $params = null)
    {
        $this->fetchStyle = $fetchStyle;
    }

    private function __executeWhenStringQueryEmpty()
    {
        if (is_string($this->statement) && empty($this->resultSet)) {
            $this->resultSet = $this->parent->client->query($this->statement);
            $this->afterExecute();
        }
    }

    private function transBoth($rawData)
    {
        $temp = [];
        foreach ($rawData as $row) {
            $rowSet = [];
            $i = 0;
            foreach ($row as $key => $value) {
                $rowSet[$key] = $value;
                $rowSet[$i++] = $value;
            }
            $temp[] = $rowSet;
        }

        return $temp;
    }

    private function transStyle(
        $rawData,
        $fetchStyle = null,
        $fetchArgument = null,
        $ctorArgs = null
    )
    {
        if (! is_array($rawData)) {
            return false;
        }
        if (empty($rawData)) {
            return $rawData;
        }

        $fetchStyle = is_null($fetchStyle) ? $this->fetchStyle : $fetchStyle;
        $ctorArgs = is_null($ctorArgs) ? [] : $ctorArgs;

        $resultSet = [];
        switch ($fetchStyle) {
            case PDO::FETCH_BOTH:
                $resultSet = $this->transBoth($rawData);
                break;
            case PDO::FETCH_COLUMN:
                $resultSet = array_column(
                    is_numeric($fetchArgument) ? $this->transBoth($rawData) : $rawData,
                    $fetchArgument
                );
                break;
            case PDO::FETCH_OBJ:
                foreach ($rawData as $row) {
                    $resultSet[] = (object) $row;
                }
                break;
            case PDO::FETCH_NUM:
                foreach ($rawData as $row) {
                    $resultSet[] = array_values($row);
                }
                break;
            case PDO::FETCH_ASSOC:
            default:
                return $rawData;
        }

        return $resultSet;
    }

    public function fetch(
        $fetchStyle = null,
        $cursorOrientation = null,
        $cursorOffset = null,
        $fetchArgument = null
    )
    {
        $this->__executeWhenStringQueryEmpty();

        $cursorOrientation = is_null($cursorOrientation) ? PDO::FETCH_ORI_NEXT : $cursorOrientation;
        $cursorOffset = is_null($cursorOffset) ? 0 : (int) $cursorOffset;

        switch ($cursorOrientation) {
            case PDO::FETCH_ORI_ABS:
                $this->cursor = $cursorOffset;
                break;
            case PDO::FETCH_ORI_REL:
                $this->cursor += $cursorOffset;
                break;
            case PDO::FETCH_ORI_NEXT:
            default:
                $this->cursor++;
        }

        if (isset($this->resultSet[$this->cursor])) {
            $result = $this->resultSet[$this->cursor];
            unset($this->resultSet[$this->cursor]);
        } else {
            $result = false;
        }

        if (empty($result)) {
            return $result;
        } else {
            return $this->transStyle([$result], $fetchStyle, $fetchArgument)[0];
        }
    }

    /**
     * Returns a single column from the next row of a result set or FALSE if there are no more rows.
     *
     * @param int|null $columnNumber
     *
     * 0-indexed number of the column you wish to retrieve from the row.
     * If no value is supplied, PDOStatement::fetchColumn() fetches the first column.
     *
     * @return bool|mixed
     */
    public function fetchColumn($columnNumber = null)
    {
        $columnNumber = is_null($columnNumber) ? 0 : $columnNumber;
        $this->__executeWhenStringQueryEmpty();

        return $this->fetch(PDO::FETCH_COLUMN, PDO::FETCH_ORI_NEXT, 0, $columnNumber);
    }

    public function fetchAll($fetchStyle = null, $fetchArgument = null, $ctorArgs = null)
    {
        $this->__executeWhenStringQueryEmpty();
        $resultSet = $this->transStyle($this->resultSet, $fetchStyle, $fetchArgument, $ctorArgs);
        $this->resultSet = [];

        return $resultSet;
    }
}
