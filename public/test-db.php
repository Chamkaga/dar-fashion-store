<?php

require_once("../app/config/db.php");

$database = new Database();

$conn = $database->connect();

if($conn){
    echo "DATABASE CONNECTED SUCCESSFULLY";
}else{
    echo "DATABASE CONNECTION FAILED";
}

?>