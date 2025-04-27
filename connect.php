<?php
if (defined('CONNECT_PHP_INCLUDED')) {
    define('CONNECT_PHP_INCLUDED', true);
}
ob_start();
try {
    $conn = new PDO("mysql:host=localhost;dbname=smobile2;charset=utf8", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo $e->getMessage();
}

function selectAll($sql, $params = [])
{
    global $conn;
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function exSQL($sql, $params = [])
{
    global $conn;
    $stmt = $conn->prepare($sql);
    return $stmt->execute($params);
}

function rowCount($sql, $params = [])
{
    global $conn;
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}

date_default_timezone_set('Asia/Ho_Chi_Minh');
$timestamp = time();
$today = date('d-m-Y H:i:s', $timestamp);
?>