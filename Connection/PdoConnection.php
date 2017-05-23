<?php

namespace Brouzie\Sphinxy\Connection;

use Brouzie\Sphinxy\Exception\ConnectionException;

class PdoConnection implements ConnectionInterface
{
    /**
     * @var \PDO
     */
    private $pdo;

    private $dsn;

    public function __construct($dsn)
    {
        $this->dsn = $dsn;
    }

    public function query($query)
    {
        $this->initialize();

        try {
            $stmt = $this->pdo->query($query);
        } catch (\PDOException $e) {
            throw new ConnectionException($e->getMessage(), 0, $e);
        }

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function multiQuery($query, array $resultSetNames = array())
    {
        $this->initialize();

        try {
            $stmt = $this->pdo->query($query);
        } catch (\PDOException $e) {
            throw new ConnectionException($e->getMessage(), 0, $e);
        }

        $results = array();
        $i = 0;
        do {
            $key = isset($resultSetNames[$i]) ? $resultSetNames[$i] : $i;
            $results[$key] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            ++$i;
        } while ($stmt->nextRowset());

        return $results;
    }

    public function exec($query)
    {
        $this->initialize();

        try {
            return $this->pdo->exec($query);
        } catch (\PDOException $e) {
            throw new ConnectionException($e->getMessage(), 0, $e);
        }
    }

    public function quote($value)
    {
        $this->initialize();

        if (false === $value = $this->pdo->quote((string) $value)) {
            throw new ConnectionException($this->pdo->errorInfo(), $this->pdo->errorCode());
        }

        return $value;
    }

    protected function initialize()
    {
        if (null === $this->pdo) {
            try {
                $this->pdo = new \PDO($this->dsn, null, null, array(\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION));
                $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            } catch (\PDOException $e) {
                throw new ConnectionException($e->getMessage(), 0, $e);
            }
        }
    }

    public function checkConnection()
    {
        try {
            $this->query('SELECT 1');
        } catch (ConnectionException $e) {
            $this->pdo = null;
        }
    }
}
