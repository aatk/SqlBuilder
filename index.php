<?php
/**
 * ДЕМОНСТРАЦИЯ РАБОТЫ БИЛДЕРА
 */

include_once "source/SqlBuilder.class.php";

$SqlBuilder = new SqlBuilder();

$result = $SqlBuilder->Sql([
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

print_r($result);


$sql      = new SqlBuilder();
$sql_text = $sql->select([
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

$params = $sql->GetSQLParams();
print_r($params);


$SqlBuilder = new SqlBuilder();
$sql_text   = $SqlBuilder->insert([
    "table"  => "TABLE3(ALIAS3)",
    "items"  => [ "item1", "item2", "item3" ],
    "values" => [ 123456, "test2", "test3" ],
]);
print_r($sql_text);

$params = $SqlBuilder->GetSQLParams();
print_r($params);


$SqlBuilder = new SqlBuilder();
$sql_text   = $SqlBuilder->update([
    "table"  => "TABLE3(TABLE3)",
    "values" => [
        "item1" => "test2",
        "item2" => 123456,
    ],
]);
print_r($sql_text);

$params = $SqlBuilder->GetSQLParams();
print_r($params);


$SqlBuilder = new SqlBuilder();
$sql_text   = $SqlBuilder->delete([
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
