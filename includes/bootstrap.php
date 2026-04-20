<?php
declare(strict_types=1);
session_start();
date_default_timezone_set('America/Sao_Paulo');

define('ARMA_ROOT', dirname(__DIR__));
define('ARMA_CONFIG', ARMA_ROOT . '/config/config.php');

require_once __DIR__ . '/Config.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Audit.php';
