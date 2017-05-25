<?php

namespace Brouzie\Sphinxy\Logging;

/**
 * Logger that store backtraces.
 *
 * @author Konstantin Myakshin <koc-dp@yandex.ru>
 */
class TraceLogger extends DebugStack
{
    /**
     * {@inheritdoc}
     */
    public function startQuery($sql, array $params = null)
    {
        $backtrace = $this->getBactrace();

        $this->start = microtime(true);
        $this->queries[++$this->currentQuery] = array(
            'sql' => $sql,
            'params' => $params,
            'executionMS' => 0,
            'stacktrace' => $backtrace,
        );
    }

    private function getBactrace()
    {
        //TODO: format args using ValueExporter() class
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        foreach ($backtrace as $key => $debug) {
            //FIX: Пропускам трейсы где нет класса а есть 'function' =>  string(20) "call_user_func_array"
            if (!isset($debug['class'])) {
                continue;
            }
            
            if (!$this->isInternalClass($debug['class'])) {
                return array_slice($backtrace, $key - 1, 10);
            }
        }

        return array();
    }

    private function isInternalClass($class)
    {
        return strpos($class, 'Brouzie\\Sphinxy') === 0 || strpos($class, 'Brouzie\\Bundle\\SphinxyBundle') === 0;
    }
}
