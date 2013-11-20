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

        $stmt = $this->pdo->query($query);
        if (!is_object($stmt)) {
            list($code, , $message) = $this->pdo->errorInfo();

            throw new ConnectionException($message, $code);
        }
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $result;
    }

    public function exec($query)
    {
        $this->initialize();

        $result = $this->pdo->exec($query);
        if (false === $result) {
            list($code, , $message) = $this->pdo->errorInfo();

            throw new ConnectionException($message, $code);
        }

        return $result;
    }

    public function quote($value)
    {
        $this->initialize();

        if (false === $value = $this->pdo->quote((string)$value)) {
            throw new ConnectionException($this->pdo->errorInfo(), $this->pdo->errorCode());
        }

        return $value;
    }

    protected function initialize()
    {
        if (null === $this->pdo) {
            $this->pdo = new \PDO($this->dsn);
        }
    }
}
