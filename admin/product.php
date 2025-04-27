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
        // Filter parameters
        $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
        $category = isset($_GET['category']) ? $_GET['category'] : '';
        $min_price = isset($_GET['min_price']) ? $_GET['min_price'] : '';
        $max_price = isset($_GET['max_price']) ? $_GET['max_price'] : '';

        $where_clauses = [];
        $params = [];

        if ($keyword !== '') {
            $where_clauses[] = "sp.ten LIKE ?";
            $params[] = '%' . $keyword . '%';
        }

        if ($category !== '' && is_numeric($category)) {
            $where_clauses[] = "sp.id_danhmuc = ?";
            $params[] = $category;
        }

        if ($min_price !== '' && is_numeric($min_price)) {
            $where_clauses[] = "sp.gia >= ?";
            $params[] = $min_price;
        }

        if ($max_price !== '' && is_numeric($max_price)) {
            $where_clauses[] = "sp.gia <= ?";
            $params[] = $max_price;
        }

        $where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

        // Lấy danh sách danh mục để hiển thị trong select
        $sql_categories = "SELECT * FROM danhmuc";
        $categories = selectAll($sql_categories, []);

        // Lấy danh sách sản phẩm
        $item_per_page = !empty($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
        $current_page = !empty($_GET['page']) ? (int)$_GET['page'] : 1;
        $offset = ($current_page - 1) * $item_per_page;

        try {
            $numrow = rowCount("SELECT sp.* FROM sanpham sp LEFT JOIN danhmuc dm ON sp.id_danhmuc = dm.id_dm $where_sql", $params);
            $totalpage = ceil($numrow / $item_per_page);
            $sql_products = "SELECT sp.*, dm.danhmuc AS ten_danhmuc 
                            FROM sanpham sp 
                            LEFT JOIN danhmuc dm ON sp.id_danhmuc = dm.id_dm 
                            $where_sql 
                            ORDER BY sp.id DESC 
                            LIMIT $item_per_page OFFSET $offset";
            $products = selectAll($sql_products, $params);
        } catch (PDOException $e) {
            echo "<div class='alert alert-danger'>Lỗi truy vấn sản phẩm: " . htmlspecialchars($e->getMessage()) . "</div>";
            exit();
        }
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
                        <!-- Filter Form -->
                        <form method="GET" class="mb-4">
                            <div class="row">
                                <div class="col-md-3 form-group">
                                    <label for="keyword">Tìm kiếm</label>
                                    <input type="text" name="keyword" id="keyword" class="form-control" value="<?= htmlspecialchars($keyword) ?>" placeholder="Tìm theo tên sản phẩm">
                                </div>
                                <div class="col-md-3 form-group">
                                    <label for="category">Danh mục</label>
                                    <select name="category" id="category" class="form-control">
                                        <option value="" <?= $category == '' ? 'selected' : '' ?>>Tất cả</option>
                                        <?php foreach ($categories as $cat) { ?>
                                            <option value="<?= $cat['id_dm'] ?>" <?= $category == $cat['id_dm'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['danhmuc']) ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="col-md-3 form-group">
                                    <label for="min_price">Giá tối thiểu</label>
                                    <input type="number" name="min_price" id="min_price" class="form-control" value="<?= htmlspecialchars($min_price) ?>" placeholder="Giá tối thiểu">
                                </div>
                                <div class="col-md-3 form-group">
                                    <label for="max_price">Giá tối đa</label>
                                    <input type="number" name="max_price" id="max_price" class="form-control" value="<?= htmlspecialchars($max_price) ?>" placeholder="Giá tối đa">
                                </div>
                                <div class="col-md-3 form-group mt-4">
                                    <button type="submit" class="btn btn-primary">Lọc</button>
                                    <a href="product.php" class="btn btn-secondary">Xóa bộ lọc</a>
                                </div>
                            </div>
                        </form>
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
                                                <td><?= $product['status'] == 1 ? 'Ẩn' : 'Hiển thị' ?></td>
                                                <td>
                                                    <a href="editproduct.php?id=<?= $product['id'] ?>" class="btn btn-warning btn-sm">Sửa</a>
                                                    <a href="deleteproduct.php?id=<?= $product['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc muốn xóa?')">Xóa</a>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    <?php } ?>
                                </tbody>
                            </table>
                            <!-- Pagination -->
                            <div class="col-lg-12">
                                <div class="pagination">
                                    <nav aria-label="Page navigation example">
                                        <ul class="pagination justify-content-center">
                                            <?php for ($num = 1; $num <= $totalpage; $num++) { ?>
                                                <?php if ($num != $current_page) { ?>
                                                    <?php if ($num > $current_page - 3 && $num < $current_page + 3) { ?>
                                                        <li class="page-item"><a class="btn btn-outline-secondary" href="?per_page=<?= $item_per_page ?>&page=<?= $num ?>&keyword=<?= urlencode($keyword) ?>&category=<?= urlencode($category) ?>&min_price=<?= urlencode($min_price) ?>&max_price=<?= urlencode($max_price) ?>"><?= $num ?></a></li>
                                                    <?php } ?>
                                                <?php } else { ?>
                                                    <strong class="page-item"><a class="btn btn-outline-secondary"><?= $num ?></a></strong>
                                                <?php } ?>
                                            <?php } ?>
                                        </ul>
                                    </nav>
                                </div>
                            </div>
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