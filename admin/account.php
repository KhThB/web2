<?php 
include 'header.php';

if (isset($_COOKIE["user"])) {
    $user = $_COOKIE["user"];
    $sql = "SELECT * FROM taikhoan WHERE taikhoan = ?";
    $result = selectAll($sql, [$user]);
    $permission = 0;
    foreach ($result as $row) {
        $permission = $row['phanquyen'];
    }
    if ($permission == 1) {
        // Xử lý khóa/mở khóa tài khoản
        if (isset($_GET["id"])) {
            $id = (int)$_GET['id'];
            if (rowCount("SELECT * FROM taikhoan WHERE id=? AND status=1", [$id]) > 0) {
                exSQL("UPDATE taikhoan SET status=0 WHERE id=? AND status=1", [$id]);
                header('location:account.php');
                exit();
            } elseif (rowCount("SELECT * FROM taikhoan WHERE id=? AND status=0", [$id]) > 0) {
                exSQL("UPDATE taikhoan SET status=1 WHERE id=? AND status=0", [$id]);
                header('location:account.php');
                exit();
            }
        }

        // Filter parameters
        $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
        $permission_filter = isset($_GET['permission']) ? $_GET['permission'] : '';
        $status_filter = isset($_GET['status']) ? $_GET['status'] : '';

        $where_clauses = [];
        $params = [];

        if ($keyword !== '') {
            $where_clauses[] = "(hoten LIKE ? OR taikhoan LIKE ?)";
            $keyword_like = '%' . $keyword . '%';
            $params[] = $keyword_like;
            $params[] = $keyword_like;
        }

        if ($permission_filter !== '' && in_array($permission_filter, ['0', '1'])) {
            $where_clauses[] = "phanquyen = ?";
            $params[] = $permission_filter;
        }

        if ($status_filter !== '' && in_array($status_filter, ['0', '1'])) {
            $where_clauses[] = "status = ?";
            $params[] = $status_filter;
        }

        $where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

        // Lấy danh sách tài khoản
        $item_per_page = !empty($_GET['per_page']) ? (int)$_GET['per_page'] : 8;
        $current_page = !empty($_GET['page']) ? (int)$_GET['page'] : 1;
        $offset = ($current_page - 1) * $item_per_page;

        try {
            $numrow = rowCount("SELECT * FROM taikhoan $where_sql", $params);
            $totalpage = ceil($numrow / $item_per_page);
            $sql_accounts = "SELECT * FROM taikhoan $where_sql ORDER BY status ASC LIMIT $item_per_page OFFSET $offset";
            $accounts = selectAll($sql_accounts, $params);
        } catch (PDOException $e) {
            echo "<div class='alert alert-danger'>Lỗi truy vấn tài khoản: " . htmlspecialchars($e->getMessage()) . "</div>";
            exit();
        }
?>
<!-- partial -->
<div class="main-panel">
    <div class="content-wrapper">
        <div class="row">
            <div class="col-12 grid-margin">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title addfont">Tài Khoản</h4>
                        <!-- Filter Form -->
                        <form method="GET" class="mb-4">
                            <div class="row">
                                <div class="col-md-3 form-group">
                                    <label for="keyword">Tìm kiếm</label>
                                    <input type="text" name="keyword" id="keyword" class="form-control" value="<?= htmlspecialchars($keyword) ?>" placeholder="Tìm theo họ tên hoặc email">
                                </div>
                                <div class="col-md-3 form-group">
                                    <label for="permission">Loại tài khoản</label>
                                    <select name="permission" id="permission" class="form-control">
                                        <option value="" <?= $permission_filter == '' ? 'selected' : '' ?>>Tất cả</option>
                                        <option value="1" <?= $permission_filter == '1' ? 'selected' : '' ?>>Admin</option>
                                        <option value="0" <?= $permission_filter == '0' ? 'selected' : '' ?>>Khách hàng</option>
                                    </select>
                                </div>
                                <div class="col-md-3 form-group">
                                    <label for="status">Trạng thái</label>
                                    <select name="status" id="status" class="form-control">
                                        <option value="" <?= $status_filter == '' ? 'selected' : '' ?>>Tất cả</option>
                                        <option value="0" <?= $status_filter == '0' ? 'selected' : '' ?>>Đang hoạt động</option>
                                        <option value="1" <?= $status_filter == '1' ? 'selected' : '' ?>>Khóa</option>
                                    </select>
                                </div>
                                <div class="col-md-3 form-group mt-4">
                                    <button type="submit" class="btn btn-primary">Lọc</button>
                                    <a href="account.php" class="btn btn-secondary">Xóa bộ lọc</a>
                                </div>
                            </div>
                        </form>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th class="addfont" style="width: 100px">STT</th>
                                        <th class="addfont" style="width: 500px">Họ Tên</th>
                                        <th class="addfont" style="width: 400px">Tài Khoản (Email)</th>
                                        <th class="addfont" style="width: 300px">Loại Tài Khoản</th>
                                        <th class="addfont" style="width: 300px">Trạng thái</th>
                                        <th class="addfont"><a type="button" class="btn btn-success btn-fw" style="width: 204px" href="addaccount.php">Thêm Tài Khoản</a></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $stt = 1;
                                    if (empty($accounts)) {
                                        echo "<tr><td colspan='6' class='text-center'>Không có tài khoản nào để hiển thị.</td></tr>";
                                    } else {
                                        foreach ($accounts as $row) {
                                    ?>
                                        <tr>
                                            <td><?= $stt++ ?></td>
                                            <td>
                                                <img src="<?= empty($row['anh']) ? '../img/account/user.png' : '../img/account/' . $row['anh'] ?>" alt="image">
                                                <span class="addfont"><?= htmlspecialchars($row['hoten']) ?></span>
                                            </td>
                                            <td class="addfont"><?= htmlspecialchars($row['taikhoan']) ?></td>
                                            <td class="addfont"><?= $row['phanquyen'] == 1 ? 'Admin' : 'Khách hàng' ?></td>
                                            <td class="addfont"><?= $row['status'] == 0 ? 'Đang hoạt động' : 'Khóa' ?></td>
                                            <td>
                                                <a type="button" class="btn btn-primary btn-icon-text addfont" href="editaccount.php?id=<?= $row['id'] ?>">
                                                    <i class="mdi mdi-file-check btn-icon-prepend"></i> Sửa
                                                </a>
                                                <?php if ($row['status'] == 0) { ?>
                                                    <a type="button" class="btn btn-danger btn-icon-text addfont" style="width: 120px" href="?id=<?= $row['id'] ?>" onclick="return confirm('Bạn có muốn khóa tài khoản này không?')">
                                                        <i class="mdi mdi-account-off btn-icon-prepend"></i> Khóa
                                                    </a>
                                                <?php } else { ?>
                                                    <a type="button" class="btn btn-secondary btn-icon-text addfont" style="width: 120px" href="?id=<?= $row['id'] ?>" onclick="return confirm('Bạn có muốn mở tài khoản này không?')">
                                                        <i class="mdi mdi-account-outline btn-icon-prepend"></i> Mở Khóa
                                                    </a>
                                                <?php } ?>
                                            </td>
                                        </tr>
                                    <?php
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                            <div class="col-lg-12">
                                <div class="pagination">
                                    <nav aria-label="Page navigation example">
                                        <ul class="pagination justify-content-center">
                                            <?php for ($num = 1; $num <= $totalpage; $num++) { ?>
                                                <?php if ($num != $current_page) { ?>
                                                    <?php if ($num > $current_page - 3 && $num < $current_page + 3) { ?>
                                                        <li class="page-item"><a class="btn btn-outline-secondary" href="?per_page=<?= $item_per_page ?>&page=<?= $num ?>&keyword=<?= urlencode($keyword) ?>&permission=<?= urlencode($permission_filter) ?>&status=<?= urlencode($status_filter) ?>"><?= $num ?></a></li>
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
<?php
    } else {
        include '404.php';
    }
} else {
    include '404.php';
}
include 'footer.php';
?>