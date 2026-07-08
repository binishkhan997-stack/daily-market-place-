<?php

$host="localhost";
$db="dailymarket";
$user="root";
$pass="";

try{

$conn=new PDO(

"mysql:host=$host;dbname=$db;charset=utf8mb4",

$user,

$pass

);

$conn->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);

}catch(PDOException $e){

die($e->getMessage());

}
