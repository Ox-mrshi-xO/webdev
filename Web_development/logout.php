<?php
require_once __DIR__ . '/config/init.php';
$auth = new Auth();
$auth->logout();
header('Location: login.php');
exit;
