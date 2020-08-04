# SqlBuilder

## About

`SqlBuilder` предназначен для формирования и передачи на выполнения Sql запросов.

## Table of Contents

- [Examples](#examples)
- [Methods](#methods)
  - [Select](#Select)
  - [Insert](#Insert)
  - [Update](#Update)
  - [Delete](#Delete)

## Examples

```php
$sql = new SqlBuilder();

$res = $sql->Sql([
    "select" => [
        "table" => "TABLE(ALIAS)",
        "items" => [ "ALIAS.item1(sel)", "ALIAS.item2", "ALIAS.item3" ],
        "join"  => [
            "table" => "TABLE2(ALIAS2)[>]",
            "where" => [
                "OR" => [
                    "item1[><]" => [ "param1", "param2" ],
                    "item2[in]" => [ "param1", "param2" ],
                    "item3[mb]" => "%fulltext%",
                    "item4[~]"  => "liketest",
                    "AND"       => [ "item5" => "item5_test", "item6" => 123456 ],
                ],
            ],
        ],
        "where" => [
            "OR" => [
                "ALIAS.item1[><]" => [ "param1", "param2" ],
                "ALIAS.item2[in]" => [ "param1", "param2" ],
                "ALIAS.item3[mb]" => "%param1%",
                "ALIAS.item4[~]"  => "like",
                "AND"             => [ "ALIAS.a" => "test", "ALIAS.b" => "12345" ],
            ],
        ],
        "order" => [
            "ASC" => [ "ALIAS.id" ],
        ],
        "group" => [ "ALIAS.item1", "ALIAS.item2" ],
        "limit" => [ 1, 5 ],
    ],
    
    "insert(1)" => [
        "table"  => "TABLE3(ALIAS3)",
        "items"  => [ "item1", "item2", "item3" ],
        "values" => [ 123456, "test2", "test3" ],
    ],
    
    "insert(2)" => [
        "table"  => "TABLE4(ALIAS4)",
        "values" => [
            "select" => [
                "table" => "TABLE2(ALIAS2)",
                "items" => [ "item1", "item2", "item3" ],
                "where" => [ "id[<=]" => 100 ],
            ],
        ],
    ],
    
    "update" => [
        "table"  => "TABLE3(TABLE3)",
        "values" => [
            "item1" => "test2",
            "item2" => [
                "select" => [
                    "table" => "TABLE2(ALIAS2)",
                    "items" => [ "item2" ],
                ],
            ],
        ],
    ],
    
    "delete" => [
        "table" => "TABLE4(ALIAS4)",
        "where" => [
            "OR" => [
                "id[><]"    => [ 10, 20 ],
                "item2[in]" => [ "param1", "param2" ],
                "item3[mb]" => "%param1%",
                "item4[~]"  => "like",
                "AND"       => [ "item5" => 1, "item6" => "test" ],
            ],
        ],
        "order" => [
            "ASC" => [ "id" ],
        ],
        "limit" => [ 1, 5 ],
    ],
]);

print_r($res);
```

Result

```
Array
(
    [sqls] => Array
        (
            [0] => SELECT ALIAS.item1 AS sel, ALIAS.item2, ALIAS.item3
FROM TABLE AS ALIAS
LEFT JOIN TABLE2 AS ALIAS2 ON (item1 BETWEEN :param0 AND :param1 OR item2 IN (:param0, :param1) OR MATCH (item3) AGAINST (':param2' IN BOOLEAN MODE) OR item4 LIKE :param3 OR (item5 = :param4 AND item6 = :param5))
WHERE (ALIAS.item1 BETWEEN :param0 AND :param1 OR ALIAS.item2 IN (:param0, :param1) OR MATCH (ALIAS.item3) AGAINST (':param6' IN BOOLEAN MODE) OR ALIAS.item4 LIKE :param7 OR (ALIAS.a = :param8 AND ALIAS.b = :param9))
GROUP BY ALIAS.item1, ALIAS.item2 ASC
ORDER BY ALIAS.id ASC
LIMIT 1,5
            [1] => INSERT INTO TABLE3 (item1, item2, item3)
VALUES (:param5, :param10, :param11)
            [2] => INSERT INTO TABLE4 
SELECT item1, item2, item3
FROM TABLE2 AS ALIAS2
WHERE id <= :param12
            [3] => UPDATE TABLE3
SET item1 = :param10,
item2 = (SELECT item2
FROM TABLE2 AS ALIAS2)
ORDER BY TABLE3.id DESC
            [4] => DELETE FROM TABLE4
WHERE (id BETWEEN :param13 AND :param14 OR item2 IN (:param0, :param1) OR MATCH (item3) AGAINST (':param6' IN BOOLEAN MODE) OR item4 LIKE :param7 OR (item5 = :param15 AND item6 = :param8))
ORDER BY id ASC
LIMIT 1,5
        )

    [params] => Array
        (
            [0] => param1
            [1] => param2
            [2] => %fulltext%
            [3] => liketest
            [4] => item5_test
            [5] => 123456
            [6] => %param1%
            [7] => like
            [8] => test
            [9] => 12345
            [10] => test2
            [11] => test3
            [12] => 100
            [13] => 10
            [14] => 20
            [15] => 1
        )

)
```

## Methods

### Select `$SqlBuilder->select(...)`

Example:

```php
$SqlBuilder = new SqlBuilder();
$sql_text = $SqlBuilder->select([
    "table" => "TABLE(ALIAS)",
    "items" => [ "ALIAS.item1(sel)", "ALIAS.item2", "ALIAS.item3" ],
    "join"  => [
        "table" => "TABLE2(ALIAS2)[>]",
        "where" => [
            "OR" => [
                "item1[><]" => [ "param1", "param2" ],
                "item2[in]" => [ "param1", "param2" ],
                "item3[mb]" => "%fulltext%",
                "item4[~]"  => "liketest",
                "AND"       => [ "item5" => "item5_test", "item6" => 123456 ],
            ],
        ],
    ],
    "where" => [
        "OR" => [
            "ALIAS.item1[><]" => [ "param1", "param2" ],
            "ALIAS.item2[in]" => [ "param1", "param2" ],
            "ALIAS.item3[mb]" => "%param1%",
            "ALIAS.item4[~]"  => "like",
            "AND"             => [ "ALIAS.a" => "test", "ALIAS.b" => "12345" ],
        ],
    ],
    "order" => [
        "ASC" => [ "ALIAS.id" ],
    ],
    "group" => [ "ALIAS.item1", "ALIAS.item2" ],
    "limit" => [ 1, 5 ],
]);
print_r($sql_text);

$params = $SqlBuilder->GetSQLParams();
print_r($params);
```

Result:

```
SELECT 
    ALIAS.item1 AS sel, 
    ALIAS.item2, 
    ALIAS.item3
FROM TABLE AS ALIAS
    LEFT JOIN TABLE2 AS ALIAS2 ON (
            item1 BETWEEN :param0 AND :param1 
        OR item2 IN (:param0, :param1) 
        OR MATCH (item3) AGAINST (':param2' IN BOOLEAN MODE) 
        OR item4 LIKE :param3 
        OR (
                item5 = :param4 
            AND item6 = :param5
            )
        )
WHERE (
        ALIAS.item1 BETWEEN :param0 AND :param1 
    OR ALIAS.item2 IN (:param0, :param1) 
    OR MATCH (ALIAS.item3) AGAINST (':param6' IN BOOLEAN MODE) 
    OR ALIAS.item4 LIKE :param7 
    OR (
            ALIAS.a = :param8 
        AND ALIAS.b = :param9
        )
    )
GROUP BY 
    ALIAS.item1, ALIAS.item2 ASC
ORDER BY ALIAS.id ASC
LIMIT 1,5

Array
(
    [0] => param1
    [1] => param2
    [2] => %fulltext%
    [3] => liketest
    [4] => item5_test
    [5] => 123456
    [6] => %param1%
    [7] => like
    [8] => test
    [9] => 12345
)
```

### Insert `$SqlBuilder->insert(...)`


Example:

```php
$SqlBuilder = new SqlBuilder();
$sql_text = $SqlBuilder->insert([
    "table"  => "TABLE3(ALIAS3)",
    "items"  => [ "item1", "item2", "item3" ],
    "values" => [ 123456, "test2", "test3" ],
]);
print_r($sql_text);

$params = $SqlBuilder->GetSQLParams();
print_r($params);
```

Result:

```
INSERT INTO TABLE3 (item1, item2, item3)
VALUES (:param0, :param1, :param2)

Array
(
    [0] => 123456
    [1] => test2
    [2] => test3
)
```

### Update `$SqlBuilder->update(...)`

Example:

```php
$SqlBuilder = new SqlBuilder();
$sql_text = $SqlBuilder->update([
    "table"  => "TABLE3(TABLE3)",
    "values" => [
        "item1" => "test2",
        "item2" => 123456,
    ],
]);
print_r($sql_text);

$params = $SqlBuilder->GetSQLParams();
print_r($params);
```

Result:

```
UPDATE TABLE3
    SET item1 = :param0,
        item2 = :param1

Array
(
    [0] => test2
    [1] => 123456
)
```

### Delete `$SqlBuilder->delete(...)`

Example:
```php
$SqlBuilder = new SqlBuilder();
$sql_text = $SqlBuilder->delete([
    "table" => "TABLE4(ALIAS4)",
    "where" => [
        "OR" => [
            "id[><]"    => [ 10, 20 ],
            "item2[in]" => [ "param1", "param2" ],
            "item3[mb]" => "%param1%",
            "item4[~]"  => "like",
            "AND"       => [ "item5" => 1, "item6" => "test" ],
        ],
    ],
    "order" => [
        "ASC" => [ "id" ],
    ],
    "limit" => [ 1, 5 ],
]);
print_r($sql_text);

$params = $SqlBuilder->GetSQLParams();
print_r($params);
```

Result:
```
DELETE FROM TABLE4
WHERE 
    (
            id BETWEEN :param0 AND :param1 
        OR item2 IN (:param2, :param3) 
        OR MATCH (item3) AGAINST (':param4' IN BOOLEAN MODE) 
        OR item4 LIKE :param5 
        OR (
                item5 = :param6 
            AND item6 = :param7
            )
    )
ORDER BY id ASC
LIMIT 1,5

Array
(
    [0] => 10
    [1] => 20
    [2] => param1
    [3] => param2
    [4] => %param1%
    [5] => like
    [6] => 1
    [7] => test
)
```
