<?php

interface SQLExecInterface
{
    /**
     * Функция выполняет запрос и возвращает результат
     *
     * @param string $sqltext
     * @param array  $sqlparams
     *
     * @return array
     */
    public function sql(string $sqltext, array $sqlparams);
    
    /**
     * Функция возвращает список increment по последнему insert
     *
     * @return array
     */
    public function ids();
    
    /**
     * Функци записывает в ваш кеш схему и результат выполнения
     * Рекомендуется использовать вместе с MEMCACHE
     *
     * @param string $sql
     * @param array  $params
     * @param array  $data
     *
     * @return mixed
     */
    public function save_data_to_cache(array $shema, array $data);
    
    /**
     * Функция читает из вашего кеша результат выполнения по входящей схеме
     * Рекомендуется использовать вместе с MEMCACHE
     *
     * @param string $sql
     * @param array  $params
     *
     * @return array
     */
    public function load_data_from_cache(array $shema);
}

class SqlBuilder
{
    private $type             = "MYSQL";
    private $executive        = false;
    private $cached           = false;
    private $savetosession    = false;
    private $Object           = null;
    private $sqlstorage       = [];
    private $paramarray       = [];
    private $sqlparamtemplate = ":param";
    
    /**
     * SqlBuilder constructor.
     *
     * @param string                $type
     * @param false                 $executive
     * @param false                 $save_to_session
     * @param SQLExecInterface|null $Object
     */
    public function __construct($type = "", $executive = false, $save_to_session = false, $cached = false, SQLExecInterface $Object = null)
    {
        if ($type != "")
        {
            $this->type = strtoupper($type);
        }
        
        $this->executive = $executive;
        
        if ($save_to_session)
        {
            if (session_status() == PHP_SESSION_NONE)
            {
                session_start();
            }
            
            if (session_status() == PHP_SESSION_ACTIVE)
            {
                $this->savetosession = true;
            }
        }
        
        $this->cached = $cached;
        
        if (!is_null($Object))
        {
            $this->SetExecutiveObject($Object);
        }
        
    }
    
    //---------------=============  SETTINGS  =============---------------
    
    /**
     * @param string $template
     */
    public function SetSqlParamTemplate(string $template = null)
    {
        if (is_null($template))
        {
            $template = ":param";
        }
        $this->sqlparamtemplate = $template;
    }
    
    /**
     * @param $Object
     */
    public function SetExecutiveObject(SQLExecInterface $Object)
    {
        $this->Object = $Object;
    }
    
    //---------------=============  PRIVATE  =============---------------
    
    private function get_type_where($inputkey)
    {
        $result = [ "type" => "=", "key" => $inputkey ];
        
        $regexp = '/([\w|\d\.]+)\[([.=|.!|.~|.<|.>.in|.mb]+)\]/m';
        preg_match_all($regexp, $inputkey, $matches, PREG_SET_ORDER, 0);
        if (count($matches) > 0)
        {
            foreach ($matches as $match)
            {
                $result["key"] = $match[1];
                
                $type = $match[2];
                if ($type == "~")
                {
                    $result["type"] = "LIKE";
                }
                elseif ($type == "!~")
                {
                    $result["type"] = "NOT LIKE";
                }
                elseif ($type == "!=")
                {
                    $result["type"] = "<>";
                }
                elseif ($type == "><")
                {
                    $result["type"] = "BETWEEN";
                }
                elseif ($type == "in")
                {
                    $result["type"] = "IN";
                }
                elseif ($type == "mb")
                {
                    $result["type"] = "MB";
                }
                else
                {
                    $result["type"] = $type;
                }
            }
        }
        
        return $result;
    }
    
    private function AddParam($param)
    {
        if (in_array($param, $this->paramarray, true))
        {
            //
            $flipparamarray = array_flip($this->paramarray);
            $indexarray     = $flipparamarray[$param];
            $countstring    = (string)$indexarray;
        }
        else
        {
            $this->paramarray[] = $param;
            $countstring        = (string)(count($this->paramarray) - 1);
        }
        $nameparam = $this->sqlparamtemplate . $countstring;
        
        return $nameparam;
    }
    
    private function where_generation($array, $findkey = "AND")
    {
        $res = "";
        
        $error = false;
        $andor = [ "AND", "OR", "NOT" ];
        
        $newarray = [];
        foreach ($array as $key => $elem)
        {
            if (!in_array($key, $andor))
            {
                $key_and_type = $this->get_type_where($key);
                if ($key_and_type["type"] == "BETWEEN")
                {
                    if (is_array($elem))
                    {
                        $name1      = $this->AddParam($elem[0]);
                        $name2      = $this->AddParam($elem[1]);
                        $newarray[] = $key_and_type["key"] . " " . $key_and_type["type"] . " " . $name1 . " AND " . $name2;
                    }
                }
                elseif ($key_and_type["type"] == "IN")
                {
                    if (is_array($elem))
                    {
                        $invariant_params = [];
                        foreach ($elem as $item)
                        {
                            $invariant_params[] = $this->AddParam($item);
                        }
                        $invariant  = implode(", ", $invariant_params);
                        $newarray[] = $key_and_type["key"] . " " . $key_and_type["type"] . " (" . $invariant . ")";
                    }
                }
                elseif ($key_and_type["type"] == "MB")
                {
                    $name       = $this->AddParam($elem);
                    $newarray[] = "MATCH (" . $key_and_type["key"] . ") AGAINST ('" . $name . "' IN BOOLEAN MODE)";
                }
                //"MATCH(ct.s_brand_key) AGAINST('%s' IN BOOLEAN MODE)"
                
                else
                {
                    $name       = $this->AddParam($elem);
                    $newarray[] = $key_and_type["key"] . " " . $key_and_type["type"] . " " . $name;
                }
            }
            else
            {
                $elemkey    = $this->where_generation($elem, $key);
                $newarray[] = $elemkey;
            }
        }
        $array = $newarray;
        
        if (!$error)
        {
            //Ошибок нет, можно формировать where
            if (count($array) > 1)
            {
                //Используем $findkey
                $res = "(" . implode(" $findkey ", $array) . ")";
            }
            else
            {
                $res = $array[0];
            }
        }
        
        return $res;
    }
    
    private function generate_join($params)
    {
        $result = [
            "text"   => "",
            "tables" => [],
        ];
        
        $join      = $params["join"];
        $joinarray = $join;
        if (isset($join["table"]))
        {
            //Это отдельный жойн, сделаем из него массив
            $joinarray   = [];
            $joinarray[] = $join;
        }
        
        $text = "";
        foreach ($joinarray as $join)
        {
            $textjoin = "";
            $type     = "JOIN";
            
            $table  = $join["table"];
            $regexp = '/([\w|\d\.]+)\(([\w|\d\.]+)\)(\[([.=|.!|.~|.<|.>.in|.mb]+)\]){0,1}/m';
            preg_match_all($regexp, $table, $matches, PREG_SET_ORDER, 0);
            if (count($matches) > 0)
            {
                foreach ($matches as $match)
                {
                    $table       = $match[1];
                    $tableselect = $match[1] . " AS " . $match[2];
                    if (isset($match[4]))
                    {
                        $type = $match[4];
                    }
                }
            }
            else
            {
                $tableselect = "$table AS $table";
            }
            
            
            if ($type == ">")
            {
                $type = "LEFT JOIN";
            }
            elseif ($type == "<")
            {
                $type = "RIGHT JOIN";
            }
            elseif ($type == "><")
            {
                $type = "INNER JOIN";
            }
            elseif ($type == "<>")
            {
                $type = "FULL JOIN";
            }
            
            $textjoin = "\r\n" . $type . " " . $tableselect;
            
            $resulparams = [];
            $where       = $join["where"];
            $wheretext   = $this->where_generation($where, $resulparams);
            
            if ($wheretext != "")
            {
                $textjoin .= " ON $wheretext";
            }
            
            $text .= $textjoin;
        }
        
        $result["text"] = $text;
        
        return $result;
    }
    
    private function Get_TableTemplate($table)
    {
        $alias  = $table;
        $regexp = '/([\w|\.|\d]+)[\s]*\(([\w|\.|\d]+)\)/m';
        preg_match_all($regexp, $table, $matches, PREG_SET_ORDER, 0);
        if (count($matches) > 0)
        {
            foreach ($matches as $match)
            {
                $table = $match[1];
                $alias = $match[2];
            }
        }
        
        return [
            "table" => $table,
            "alias" => $alias,
        ];
    }
    
    private function Get_Limit($params)
    {
        $result["limit"] = "";
        if (isset($params["limit"]))
        {
            $limit = $params["limit"];
            if (is_array($limit))
            {
                $textlimit = implode(",", $limit);
            }
            else
            {
                $textlimit = $limit;
            }
            $result["limit"] = "\r\n" . "LIMIT $textlimit";
        }
        
        return $result["limit"];
    }
    
    private function Get_Order($table, $params)
    {
        $result["order"] = "";
        if (isset($params["order"]))
        {
            //ORDER BY DESC
            $type   = "DESC";
            $groups = $params["order"];
            if (is_string($groups))
            {
                $groupstext = $groups;
            }
            else
            {
                if (isset($groups["ASC"]))
                {
                    $type   = "ASC";
                    $groups = $groups["ASC"];
                }
                elseif (isset($groups["DESC"]))
                {
                    $type   = "DESC";
                    $groups = $groups["DESC"];
                }
                $groupstext = implode(", ", $groups);
            }
            
            $result["order"] .= "\r\n" . "ORDER BY $groupstext $type";
            
        }
//        else
//        {
//            $result["order"] .= "\r\n" . "ORDER BY $table.id DESC";
//        }
        
        return $result["order"];
    }
    
    private function sql_select($params)
    {
        $result = [];
        $regexp = '/([\w|\.|\d]+)[\s]*\(([\w|\.|\d]+)\)/m';
        
        /*********************************************************************
         * TABLE
         */
        
        $table = "";
        if (isset($params["table"]))
        {
            if (is_string($params["table"]))
            {
                $table = $params["table"];
            }
            else
            {
                $table = $params["table"][0];
            }
        }
        $tableselect = "";
        preg_match_all($regexp, $table, $matches, PREG_SET_ORDER, 0);
        if (count($matches) > 0)
        {
            foreach ($matches as $match)
            {
                $table       = $match[1];
                $tableselect = $match[1] . " AS " . $match[2];
            }
        }
        else
        {
            $tableselect = "$table AS $table";
        }
        
        
        /*********************************************************************
         * ITEMS
         */
        $items = "*";
        if (isset($params["items"]))
        {
            //Добавить поля
            $items        = "";
            $params_items = $params["items"];
            if (is_array($params_items))
            {
                $itemsarr = [];
                foreach ($params["items"] as $item)
                {
                    $printintem = $item;
                    
                    preg_match_all($regexp, $item, $matches, PREG_SET_ORDER, 0);
                    if (count($matches) > 0)
                    {
                        foreach ($matches as $match)
                        {
                            $itemsarr[] = $match[1] . " AS " . $match[2];
                        }
                    }
                    else
                    {
                        $itemsarr[] = $printintem;
                    }
                    
                }
                $items = implode(", ", $itemsarr);
            }
            else
            {
                if ($params_items == "*")
                {
                    $items = $table . "." . $params_items;
                }
                else
                {
                    $items = $params_items;
                }
            }
        }
        $result["items"] = $items;
        
        
        /*********************************************************************
         * FROM
         */
        $result["from"] = "";
        if ($table == "")
        {
            $result["from"] = "\r\n" . "FROM (SELECT NULL) AS NULLTABLE";
        }
        else
        {
            $result["from"] = "\r\n" . "FROM " . $tableselect;
        }
        
        
        /*********************************************************************
         * JOIN
         */
        $result["join"] = "";
        if (isset($params["join"]))
        {
            $join           = $this->generate_join($params);
            $result["join"] = $join["text"];
        }
        
        
        /*********************************************************************
         * WHERE
         */
        $result["where"] = "";
        if (isset($params["where"]))
        {
            //Добавить поля
            $resulparams = [];
            $where       = $params["where"];
            $wheretext   = $this->where_generation($where);
            if ($wheretext != "")
            {
                $result["where"] = "\r\n" . "WHERE $wheretext";
            }
        }
        
        /**********************************************************************
         * GROUP BY
         */
        $result["group"] = "";
        if (isset($params["group"]))
        {
            $type   = "ASC";
            $groups = $params["group"];
            if (is_string($groups))
            {
                $groupstext = $groups;
            }
            else
            {
                if (isset($groups["ASC"]))
                {
                    $type   = "ASC";
                    $groups = $groups["ASC"];
                }
                elseif (isset($groups["DESC"]))
                {
                    $type   = "DESC";
                    $groups = $groups["DESC"];
                }
                $groupstext = implode(", ", $groups);
            }
            
            $result["group"] = "\r\n" . "GROUP BY $groupstext $type";
            //GROUP BY ASC
        }
        
        /*********************************************************************
         * ORDER BY
         */
        $result["order"] = $this->Get_Order($table, $params);
        
        $result["limit"] = $this->Get_Limit($params);
        
        
        $text = "SELECT ";
        $text .= $result["items"];
        $text .= $result["from"];
        $text .= $result["join"];
        $text .= $result["where"];
        $text .= $result["group"];
        $text .= $result["order"];
        $text .= $result["limit"];
        
        
        return $text;
    }
    
    private function sql_insert($params)
    {
        $inserttext = "";
        $result     = [
            "items" => "",
        ];
        
        $table = "";
        if (isset($params["table"]))
        {
            $NameAndAlias = $this->Get_TableTemplate($params["table"]);
            $table        = $NameAndAlias["table"];
        }
        
        if (isset($params["items"]))
        {
            $result["items"] = "(" . implode(", ", $params["items"]) . ")";
        }
        
        if (isset($params["values"]))
        {
            $values = $params["values"];
            if (isset($values["select"]))
            {
                $result["values"] = "\r\n" . $this->sql_select($values["select"]);
            }
            else
            {
                $values_names = [];
                foreach ($params["values"] as $value)
                {
                    $values_names[] = $this->AddParam($value);
                }
                $result["values"] = "\r\n" . "VALUES (" . implode(", ", $values_names) . ")";
            }
        }
        
        if ($table != "")
        {
            $inserttext = "INSERT INTO " . $table . " " . $result["items"];
            $inserttext .= $result["values"];
        }
        
        return $inserttext;
    }
    
    private function sql_update($params)
    {
        //UPDATE [ LOW_PRIORITY ] [ IGNORE ]
        // table
        // SET column1 = expression1,
        // column2 = expression2,
        // …
        // [WHERE conditions]
        // [ORDER BY expression [ ASC | DESC ]]
        // [LIMIT number_rows];
        
        $updatetext = "";
        
        $table = "";
        if (isset($params["table"]))
        {
            $NameAndAlias = $this->Get_TableTemplate($params["table"]);
            $table        = $NameAndAlias["table"];
        }
        
        $result["set"] = "";
        if (isset($params["values"]))
        {
            $values = $params["values"];
            if (is_array($values))
            {
                $sets = [];
                foreach ($values as $key => $value)
                {
                    $addparam  = true;
                    $textvalue = $value;
                    if (is_array($value))
                    {
                        if (isset($value["select"]))
                        {
                            $textvalue = "(" . $this->sql_select($value["select"]) . ")";
                            $addparam  = false;
                        }
                    }
                    
                    $value_name = $textvalue;
                    if ($addparam)
                    {
                        $value_name = $this->AddParam($textvalue);
                    }
                    $sets[] = $key . " = " . $value_name;
                }
                $text = implode("," . "\r\n", $sets);
            }
            else
            {
                $text = $values;
            }
            
            $result["set"] = "\r\n" . "SET " . $text;
        }
        
        $result["where"] = "";
        if (isset($params["where"]))
        {
            //Добавить поля
            $resulparams = [];
            $where       = $params["where"];
            $wheretext   = $this->where_generation($where, $resulparams);
            if ($wheretext != "")
            {
                $result["where"] = "\r\n" . "WHERE $wheretext";
            }
        }
        
        $result["order"] = "";
        if (isset($params["order"]))
        {
            //ORDER BY DESC
            
            $type   = "DESC";
            $groups = $params["order"];
            if (is_string($groups))
            {
                $groupstext = $groups;
            }
            else
            {
                if (isset($groups["ASC"]))
                {
                    $type   = "ASC";
                    $groups = $groups["ASC"];
                }
                elseif (isset($groups["DESC"]))
                {
                    $type   = "DESC";
                    $groups = $groups["DESC"];
                }
                $groupstext = implode(", ", $groups);
            }
            
            $result["order"] .= "\r\n" . "ORDER BY $groupstext $type";
            
        }
        
        $result["limit"] = "";
        if (isset($params["limit"]))
        {
            $limit = $params["limit"];
            if (is_array($limit))
            {
                $textlimit = implode(",", $limit);
            }
            else
            {
                $textlimit = $limit;
            }
            $result["limit"] = "\r\n" . "LIMIT $textlimit";
        }
        
        if ($table != "")
        {
            $updatetext = "\r\n" . "UPDATE " . $table;
            $updatetext .= $result["set"];
            $updatetext .= $result["where"];
            $updatetext .= $result["order"];
            $updatetext .= $result["limit"];
        }
        
        return $updatetext;
    }
    
    private function sql_delete($params)
    {
        // DELETE [ LOW_PRIORITY ] [ QUICK ] [ IGNORE ] FROM table
        // [WHERE conditions]
        // [ORDER BY expression [ ASC | DESC ]]
        // [LIMIT number_rows];
        
        $deletetext = "";
        
        $table = "";
        if (isset($params["table"]))
        {
            $NameAndAlias = $this->Get_TableTemplate($params["table"]);
            $table        = $NameAndAlias["table"];
        }
        
        $result["where"] = "";
        if (isset($params["where"]))
        {
            //Добавить поля
            $resulparams = [];
            $where       = $params["where"];
            $wheretext   = $this->where_generation($where, $resulparams);
            if ($wheretext != "")
            {
                $result["where"] = "\r\n" . "WHERE $wheretext";
            }
        }
        
        $result["order"] = $this->Get_Order($table, $params);
        
        $result["limit"] = $this->Get_Limit($params);
        
        if ($table != "")
        {
            $deletetext = "\r\n" . "DELETE FROM " . $table;
            $deletetext .= $result["where"];
            $deletetext .= $result["order"];
            $deletetext .= $result["limit"];
        }
        
        return $deletetext;
    }
    
    // ***********************  Additional function  ****************************
    
    private function sql_has($params)
    {
        $params["items"] = "*";
        $params["limit"] = 1;
        $result          = $this->sql_select($params);
        
        return $result;
    }
    
    private function sql_get($params)
    {
        $params["limit"] = 1;
        $result          = $this->sql_select($params);
        
        return $result;
    }
    
    private function toCache(array $chema, array $data)
    {
        $result = false;
        if (($this->cached) && ($this->Object !== null))
        {
            $this->Object->save_data_to_cache($chema, $data);
            $result = true;
        }
        
        return $result;
    }
    
    private function fromCache(array $chema)
    {
        $result = false;
        if (($this->cached) && ($this->Object !== null))
        {
            $result_cache = $this->Object->load_data_from_cache($chema);
            if ($result_cache["result"])
            {
                $result = $result_cache["data"];
            }
        }
        
        return $result;
    }
    
    //---------------=============  PUBLIC  =============---------------
    
    ////    EXECUTABLE
    
    /**
     * @param $params
     *
     * @return mixed
     */
    public function select($params)
    {
        $result = $this->fromCache($params);
        if (($result === false) )
        {
            $result     = $sql = $this->sql_select($params);
            $sql_params = $this->GetSQLParams();
            if (!is_null($this->Object) && ($this->executive))
            {
                if (method_exists($this->Object, "sql"))
                {
                    $result = $this->Object->sql($sql, $sql_params);
                    $this->toCache($params, $result);
                }
            }
        }
        
        return $result;
    }
    
    /**
     * @param $params
     *
     * @return mixed
     */
    public function insert($params)
    {
        $result = $this->sql_insert($params);
        if (!is_null($this->Object) && ($this->executive))
        {
            if (method_exists($this->Object, "sql"))
            {
                $this->Object->sql($result, $this->GetSQLParams());
                $result = $this->Object->ids();
            }
        }
        
        return $result;
    }
    
    /**
     * @param $params
     *
     * @return mixed
     */
    public function update($params)
    {
        $result = $this->sql_update($params);
        if (!is_null($this->Object) && ($this->executive))
        {
            if (method_exists($this->Object, "sql"))
            {
                $result = $this->Object->sql($result, $this->GetSQLParams());
            }
        }
        
        return $result;
    }
    
    /**
     * @param $params
     *
     * @return mixed
     */
    public function delete($params)
    {
        $result = $this->sql_delete($params);
        if (!is_null($this->Object) && ($this->executive))
        {
            if (method_exists($this->Object, "sql"))
            {
                $result = $this->Object->sql($result, $this->GetSQLParams());
            }
        }
        
        return $result;
    }
    
    /**
     * @param array $params
     *
     * @return bool
     */
    public function has(array $params)
    {
        $params["items"] = "*";
        $params["limit"] = 1;
        $result          = $this->sql($params);
        
        return $result;
    }
    
    /**
     * @param array $params
     *
     * @return array
     */
    public function get(array $params)
    {
        $params["limit"] = 1;
        $result          = $this->sql($params);
        
        return $result;
    }
    
    // ***********************    Packet executive    ****************************
    
    public function GetSQLParams()
    {
        return $this->paramarray;
    }
    
    public function FreeSQLParams()
    {
        $this->paramarray = [];
    }
    
    /**
     * @param $Scheme
     *
     * @return array
     */
    public function Sql(array $Scheme)
    {
        $returnsql = [];
        
        //Start Transaction //TODO добавить начало транзакции пакета
        foreach ($Scheme as $key => $SchemeSql)
        {
            $NameAndAlias = $this->Get_TableTemplate($key);
            if (method_exists($this, $NameAndAlias["table"]))
            {
                $namefunction  = $NameAndAlias["table"];
                $aliasfunction = $NameAndAlias["alias"];
                
                $return_alias = ($aliasfunction == "" ? $namefunction : $aliasfunction);
                $result_sql   = $this->$namefunction($SchemeSql);
                if (!$this->executive)
                {
                    $result_sql_params = $this->GetSQLParams();
                    $this->FreeSQLParams();
                    
                    $returnsql[$return_alias]["sql"]    = $result_sql;
                    $returnsql[$return_alias]["params"] = $result_sql_params;
                }
                else
                {
                    $returnsql[$return_alias]["result"] = $result_sql;
                }
                
                if ($aliasfunction != "")
                {
                    $this->SetSchemeSql($aliasfunction, $SchemeSql);
                }
            }
        }
        
        //End Transaction //TODO добавить окончание транзакции пакета
        
        return $returnsql;
    }
    
    /**
     * @return array
     */
    public function GetIds()
    {
        $result = [];
        //TODO Return ids after insert
        if (method_exists($this->Object, "ids") && ($this->executive))
        {
            $result = $this->Object->ids();
        }
        
        return $result;
    }
    
    
    // ***********************    Dynamic model    ****************************
    
    /**
     * @param string $name
     * @param array  $SchemeSql
     *
     * @return bool
     */
    public function SetSchemeSql(string $name, array $SchemeSql)
    {
        $this->sqlstorage[$name] = $SchemeSql;
        if ($this->savetosession)
        {
            $_SESSION["SchemeSql"] = $this->sqlstorage;
        }
        
        return true;
    }
    
    /**
     * @param string $name
     * @param array  $variation
     *
     * @return string
     */
    public function GetSchemeSql(string $name, array $variation = [])
    {
        $Scheme    = $this->sql_GetScheme($name, $variation);
        $resultsql = $this->Sql($Scheme);
        
        return $resultsql;
    }
    
    private function sql_GetScheme($name, $variation = [])
    {
        if ($this->savetosession)
        {
            $this->sqlstorage = $_SESSION["SchemeSql"];
        }
        $SchemeSql = $this->sqlstorage[$name];
        
        if (count($variation) > 0)
        {
            foreach ($variation as $key => $value)
            {
                $SchemeSql[$key] = $value;
            }
        }
        
        return $SchemeSql;
    }
    
}
