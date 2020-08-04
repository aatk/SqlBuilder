<?php

class SQLExecDemo implements SQLExecInterface
{
    /**
     * @param string $sqltext
     * @param array  $sqlparams
     *
     * @return array
     */
    public function sql(string $sqltext, array $sqlparams) : array
    {
        // TODO: Implement sql() method.
    }
    
    /**
     * @return array
     */
    public function ids() : array
    {
        // TODO: Implement ids() method.
    }
    
    public function load_data_from_cache(string $sql, array $params)
    : array
    {
        // TODO: Implement load_data_from_cache() method.
    }
    
    public function save_data_to_cache(string $sql, array $params)
    : bool
    {
        // TODO: Implement save_data_to_cache() method.
    }
}
