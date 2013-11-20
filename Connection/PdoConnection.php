<?php

namespace Brouzie\Sphinxy\Connection;

use Brouzie\Sphinxy\Exception\ConnectionException;
use Brouzie\Sphinxy\Util;

class PdoConnection implements ConnectionInterface
{
    /**
     * @var \PDO
     */
    private $pdo;

    public function query($query)
    {
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
        $result = $this->pdo->exec($query);
        if (false === $result) {
            list($code, , $message) = $this->pdo->errorInfo();

            throw new ConnectionException($message, $code);
        }

        return $result;
    }

    public function quote($value)
    {
        if (false === $value = $this->pdo->quote((string)$value)) {
            throw new ConnectionException($this->pdo->errorInfo(), $this->pdo->errorCode());
        }

        return $value;
    }
}
