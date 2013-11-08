<?php

namespace Brouzie\Sphinxy\Logging;

interface LoggerInterface
{
    public function startQuery($sql);

    public function stopQuery();
}
