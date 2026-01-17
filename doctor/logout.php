<?php
require_once '../includes/functions.php';
// Session already started in functions.php
session_destroy();
header('Location: index.php');
exit;
?>