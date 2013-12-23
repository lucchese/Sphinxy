<?php

namespace Brouzie\Sphinxy;

use Doctrine\Common\Persistence\ConnectionRegistry;

class Registry// implements ConnectionRegistry
{
    /**
     * @var Connection[]
     */
    private $connections;

    /**
     * @var string
     */
    private $defaultConnection;

    /**
     * @param Connection[] $connections
     * @param string $defaultConnection
     */
    public function __construct($connections, $defaultConnection)
    {
        $this->connections = $connections;
        $this->defaultConnection = $defaultConnection;
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultConnectionName()
    {
        return $this->defaultConnection;
    }

    /**
     * {@inheritDoc}
     */
    public function getConnection($name = null)
    {
        if (null === $name) {
            $name = $this->defaultConnection;
        }

        if (!isset($this->connections[$name])) {
            throw new \InvalidArgumentException(sprintf('Sphinxy Connection named "%s" does not exist.', $name));
        }

        return $this->connections[$name];
    }

    /**
     * {@inheritDoc}
     */
    public function getConnections()
    {
        return $this->connections;
    }

    /**
     * {@inheritDoc}
     */
    public function getConnectionNames()
    {
        return array_keys($this->connections);
    }
}
