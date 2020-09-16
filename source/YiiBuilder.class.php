<?php

class YiiBuilder
{
    private $SQLBuilder;
    private $Builder    = [];
    private $exist_type = "select";
    
    public function __construct(SqlBuilder $Builder)
    {
        $this->SQLBuilder = $Builder;
    }
    
    public function select($items)
    {
        $this->Builder["items"] = $items;
        
        return $this;
    }
    
    public function from($TableAlias)
    {
        $this->Builder["table"] = $TableAlias;
        
        return $this;
    }
    
    public function where($ItemsWhere)
    {
        $this->Builder["where"] = $ItemsWhere;
        
        return $this;
    }
    
    public function orderBy($ItemsOrder)
    {
        $this->Builder["order"] = $ItemsOrder;
        
        return $this;
    }
    
    public function groupBy($ItemsGroup)
    {
        $this->Builder["group"] = $ItemsGroup;
        
        return $this;
    }
    
    public function having($ItemsWhere)
    {
        $this->Builder["having"] = $ItemsWhere;
        
        return $this;
    }
    
    public function limit($count)
    {
        $this->Builder["limit"][0] = $count;
        
        return $this;
    }
    
    public function offset($count)
    {
        $this->Builder["limit"][1] = $count;
        
        return $this;
    }
    
    public function leftJoin($items)
    {
        //join
        return $this;
    }
    
    public function rightJoin($items)
    {
        return $this;
    }
    
    public function innerJoin($items)
    {
        return $this;
    }
    
    public function union($items)
    {
        return $this;
    }
    
    // INSERT
    
    public function insert($columns)
    {
        $this->Builder["items"] = $columns;
        $this->exist_type       = "insert";
        
        return $this;
    }
    
    public function into($TableAlias)
    {
        $this->Builder["table"] = $TableAlias;
        
        return $this;
    }
    
    // UPDATE
    
    public function update($items)
    {
        $this->Builder["items"] = $items;
        $this->exist_type       = "update";
        
        return $this;
    }
    
    // DELETE
    
    public function delete($from)
    {
        $this->Builder["table"] = $from;
        $this->exist_type       = "delete";
        
        return $this;
    }
    
    /*****     QUERY METHODS   ******/
    
    public function all($params = [])
    {
        $result = [];
        
        $res = $this->SQLBuilder->select($this->Builder);
        if (is_array($res))
        {
            $result = $res;
        }
        
        return $result;
    }
    
    public function one($params = [])
    {
        $result = [];
        
        $res = $this->SQLBuilder->get($this->Builder);
        if (is_array($res))
        {
            $result = $res;
        }
        
        return $result;
    }
    
    public function column($params = [])
    {
        $result = [];
        
        return $result;
    }
    
    public function scalar($params = [])
    {
        $result = false;
        
        return $result;
    }
    
    public function exists($params = [])
    {
        $result = false;
        
        if ($this->exist_type == "select")
        {
            $result = $this->all($params);
        }
        elseif ($this->exist_type == "insert")
        {
            $result = $this->SQLBuilder->insert($this->Builder);
        }
        elseif ($this->exist_type == "update")
        {
            $result = $this->SQLBuilder->update($this->Builder);
        }
        elseif ($this->exist_type == "delete")
        {
            $result = $this->SQLBuilder->delete($this->Builder);
        }
        
        return $result;
    }
    
    public function count($params = [])
    {
        $result = 0;
        
        $res = $this->SQLBuilder->select($this->Builder);
        if (is_array($res))
        {
            $result = count($res);
        }
        
        return $result;
    }
}