<?php
header('Content-Type: application/json');
require_once 'functions.php';

echo json_encode(logoutCustomer());
?>
