<?php

namespace Brouzie\Sphinxy\Connection;

interface ConnectionInterface
{
    public function query($query);

    public function multiQuery($query);

    public function exec($query);

    public function quote($value);
}
