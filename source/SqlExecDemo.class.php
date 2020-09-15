<?php

/**
 * Добавьте в тело функция свой код, который обращается к БД
 * Это может быть запросы через PDO, или запросы через прямые библиотеки
 */


class SQLExecDemo implements SQLExecInterface
{
    /**
     * @param string $sqltext
     * @param array  $sqlparams
     *
     * @return array
     */
    public function sql(string $sqltext, array $sqlparams)
    {
        // TODO: Implement sql() method.
    }
    
    /**
     * @return array
     */
    public function ids()
    {
        // TODO: Implement ids() method.
    }
    
    public function save_data_to_cache(array $shema, array $data)
    {
        // TODO: Implement load_data_from_cache() method.
    }
    
    public function load_data_from_cache(array $shema)
    {
        // TODO: Implement save_data_to_cache() method.
    }
}
