<?php
// session_start(); // Handled by src/php/db_connect.php
require_once __DIR__ . '/../src/php/db_connect.php';
session_unset();
session_destroy();
header('Location: index.php');
exit;
