<?php
require_once __DIR__ . '/../src/auth.php';
session_unset();
session_destroy();
header('Location: index.php');
exit;
