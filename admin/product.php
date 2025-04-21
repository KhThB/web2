<?php
session_start();
include 'header.php';

// Bật hiển thị lỗi để debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (isset($_COOKIE["user"])) {
    $user = $_COOKIE["user"];
    $sql = "SELECT * FROM taikhoan WHERE taikhoan = ?";
    $result = selectAll($sql, [$user]);
    $permission = 0;
    foreach ($result as $row) {
        $permission = $row['phanquyen'];
    }
    if ($permission == 1) {
        // Lấy danh sách sản phẩm
        $sql_products = "SELECT sp.*, dm.danhmuc AS ten_danhmuc 
                         FROM sanpham sp 
                         LEFT JOIN danhmuc dm ON sp.id_danhmuc = dm.id_dm";
        $products = selectAll($sql_products, []);
?>
<div class="main-panel">
    <div class="content-wrapper">
        <?php
        // Hiển thị thông báo
        if (isset($_SESSION['success'])) {
            echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
            unset($_SESSION['success']);
        }
        if (isset($_SESSION['error'])) {
            echo '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
            unset($_SESSION['error']);
        }
        ?>
        <div class="row">
            <div class="col-lg-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Danh sách sản phẩm</h4>
                        <a href="addproduct.php" class="btn btn-primary mb-3">Thêm sản phẩm</a>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Tên sản phẩm</th>
                                        <th>Danh mục</th>
                                        <th>Giá</th>
                                        <th>Ảnh 1</th>
                                        <th>Lượt xem</th>
                                        <th>Trạng thái</th>
                                        <th>Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($products)) { ?>
                                        <tr>
                                            <td colspan="8" class="text-center">Không có sản phẩm nào.</td>
                                        </tr>
                                    <?php } else { ?>
                                        <?php foreach ($products as $product) { ?>
                                            <tr>
                                                <td><?= $product['id'] ?></td>
                                                <td><?= htmlspecialchars($product['ten']) ?></td>
                                                <td><?= htmlspecialchars($product['ten_danhmuc']) ?></td>
                                                <td><?= number_format($product['gia'], 0, ',', '.') ?> VND</td>
                                                <td><img src="../img/product/<?= $product['anh1'] ?>" alt="Ảnh sản phẩm" width="50"></td>
                                                <td><?= $product['luotxem'] ?></td>
                                                <td><?= $product['status'] == 1 ?  'Ẩn' : 'Hiển thị' ?></td>
                                                <td>
                                                    <a href="editproduct.php?id=<?= $product['id'] ?>" class="btn btn-warning btn-sm">Sửa</a>
                                                    <a href="deleteproduct.php?id=<?= $product['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc muốn xóa?')">Xóa</a>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
    } else {
        include '404.php';
    }
} else {
    include '404.php';
}
include 'footer.php';
?>