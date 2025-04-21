<?php
// Kết nối cơ sở dữ liệu
$host = 'localhost';  // Hoặc IP của máy chủ cơ sở dữ liệu của bạn
$dbname = 'smobile';  // Tên cơ sở dữ liệu của bạn
$username = 'root';   // Tên người dùng cơ sở dữ liệu
$password = '';       // Mật khẩu cơ sở dữ liệu (nếu có)

try {
    // Khởi tạo đối tượng PDO để kết nối cơ sở dữ liệu
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    // Thiết lập chế độ lỗi
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Kết nối thất bại: " . $e->getMessage();
    die();
}

// Hàm selectAll - Sử dụng prepared statements
function selectAll($sql, $params = [])
{
    global $conn;
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Hàm exSQL - Thực thi câu lệnh SQL như INSERT, UPDATE, DELETE
function exSQL($sql, $params = [])
{
    global $conn;
    $stmt = $conn->prepare($sql);
    return $stmt->execute($params);
}

// Hàm rowCount - Trả về số lượng bản ghi
function rowCount($sql, $params = [])
{
    global $conn;
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}

// Thiết lập múi giờ
date_default_timezone_set('Asia/Ho_Chi_Minh');
$timestamp = time();
$today = date('d-m-Y H:i:s', $timestamp);
?>
