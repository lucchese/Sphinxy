<?php

namespace Brouzie\Sphinxy;

class Registry
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
     * Gets the default connection name.
     *
     * @return string
     */
    public function getDefaultConnectionName()
    {
        return $this->defaultConnection;
    }

    /**
     * Gets the named connection.
     *
     * @param string $name the connection name (null for the default one)
     *
     * @return Connection
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
     * Gets an array of all registered connections.
     *
     * @return Connection[]
     */
    public function getConnections()
    {
        return $this->connections;
    }

    /**
     * Gets all connection names.
     *
     * @return array
     */
    public function getConnectionNames()
    {
        return array_keys($this->connections);
    }
}
