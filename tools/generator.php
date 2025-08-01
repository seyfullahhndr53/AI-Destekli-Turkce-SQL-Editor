<?php
define("ROOT_PATH", dirname(dirname(__FILE__)));
require_once('RandomDatabaseGenerator.php');

header("Content-Type:text/plain; charset=utf8;");

if (file_exists("database.db")) {
    unlink("database.db");
}
if (file_exists(ROOT_PATH . DIRECTORY_SEPARATOR . "database.db")) {
    unlink(ROOT_PATH . DIRECTORY_SEPARATOR . "database.db");
}

$generator = new Tools\RandomDatabaseGenerator();
$generator->generateDatabase();
$generator->copyDatabaseFileTo(ROOT_PATH . DIRECTORY_SEPARATOR .'database.db');
