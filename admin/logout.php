<?php
// File: /kathelia-suites/admin/logout.php
require_once __DIR__ . '/../src/php/db_connect.php';
session_unset();
session_destroy();
// go back to admin login
header('Location: login.php');
exit; 