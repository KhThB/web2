<?php
include 'header.php';
// include '../connect.php';

if (isset($_COOKIE["user"])) {
    $user = $_COOKIE["user"];
    foreach (selectAll("SELECT * FROM taikhoan WHERE taikhoan='$user'") as $row) {
        $permission = $row['phanquyen'];
    }
    
    if ($permission == 1) {
        if (isset($_GET['id'])) {
            $id = $_GET['id'];
            
            // Kiểm tra id hợp lệ
            if (!is_numeric($id) || $id <= 0) {
                die("ID sản phẩm không hợp lệ.");
            }
            
            try {
                // Xóa sản phẩm từ bảng sanpham
                $result = exSQL("DELETE FROM sanpham WHERE id = ?", [$id]);
                
                if ($result) {
                    header('location: product.php?message=Sản phẩm đã được xóa thành công');
                } else {
                    die("Không tìm thấy sản phẩm với ID: $id");
                }
            } catch (Exception $e) {
                die("Lỗi khi xóa sản phẩm: " . $e->getMessage());
            }
        } else {
            die("Thiếu ID sản phẩm.");
        }
    } else {
        die("Bạn không có quyền xóa sản phẩm.");
    }
} else {
    header('location: login_admin.php');
    exit();
}

include 'footer.php';
?>