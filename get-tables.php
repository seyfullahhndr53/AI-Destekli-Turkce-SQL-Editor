<?php

require_once 'autoload.php';

use Controllers\TableController;
use Utils\ResponseHelper;

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    ResponseHelper::errorResponse('Only GET requests allowed', 405);
}

$controller = new TableController();
$result = $controller->getAllTables();

if ($result['success']) {
    ResponseHelper::successResponse($result['data']);
} else {
    ResponseHelper::errorResponse($result['error'], 500);
}