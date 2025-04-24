<?php
header('Content-Type: application/json');
include './connect.php';

if (!isset($_GET['province_code'])) {
    echo json_encode([]);
    exit();
}

$province_code = $_GET['province_code'];
try {
    $districts = selectAll("SELECT name FROM locations WHERE parent_code=? ORDER BY name", [$province_code]);
    echo json_encode($districts);
} catch (PDOException $e) {
    echo json_encode([]);
}
?>