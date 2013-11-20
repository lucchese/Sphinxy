<?php

namespace Brouzie\Sphinxy\Logging;

interface LoggerInterface
{
    public function startQuery($sql, array $params = null);

    public function stopQuery();
}
