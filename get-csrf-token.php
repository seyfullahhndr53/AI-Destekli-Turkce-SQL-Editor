<?php

require_once 'autoload.php';

use Utils\ResponseHelper;
use Utils\InputValidator;

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    ResponseHelper::errorResponse('Only GET requests allowed', 405);
}

$token = InputValidator::generateCSRFToken();
ResponseHelper::successResponse(['token' => $token]);