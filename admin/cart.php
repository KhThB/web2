<?php
ob_start();
session_start();
include_once 'header.php';

// Kiểm tra đăng nhập
if (!isset($_COOKIE["user"])) {
    header("Location: ../login.php");
    exit();
}

$user = $_COOKIE["user"];
try {
    $taikhoan_rows = selectAll("SELECT * FROM taikhoan WHERE taikhoan=?", [$user]);
} catch (PDOException $e) {
    echo "Lỗi truy vấn taikhoan: " . htmlspecialchars($e->getMessage());
    exit();
}

if (empty($taikhoan_rows)) {
    header("Location: ../login.php");
    exit();
}

// Kiểm tra quyền truy cập
$permission = $taikhoan_rows[0]['phanquyen'];
if ($permission != 1) {
    echo "<div class='container mt-5'><h2>Bạn không có quyền truy cập trang này!</h2><a href='../index.php' class='btn btn-primary'>Quay lại</a></div>";
    include_once 'footer.php';
    exit();
}

// Xử lý hành động (cập nhật trạng thái, xóa đơn hàng)
if (isset($_GET["action"])) {
    switch ($_GET["action"]) {
        case "update":
            if (isset($_GET["id"])) {
                $id = (int)$_GET["id"];
                if (rowCount("SELECT * FROM donhang WHERE id=? AND status=1", [$id]) > 0) {
                    exSQL("UPDATE donhang SET status=2 WHERE id=? AND status=1", [$id]);
                    header('Location: cart.php?success=' . urlencode("Đơn hàng #$id đã được cập nhật thành Đang Giao."));
                    exit();
                } elseif (rowCount("SELECT * FROM donhang WHERE id=? AND status=2", [$id]) > 0) {
                    exSQL("UPDATE donhang SET status=3 WHERE id=? AND status=2", [$id]);
                    header('Location: cart.php?success=' . urlencode("Đơn hàng #$id đã được cập nhật thành Đã Giao."));
                    exit();
                } elseif (rowCount("SELECT * FROM donhang WHERE id=? AND status=4", [$id]) > 0) {
                    exSQL("DELETE FROM ctdonhang WHERE id_donhang=?", [$id]);
                    exSQL("DELETE FROM donhang WHERE id=?", [$id]);
                    header('Location: cart.php?success=' . urlencode("Đơn hàng #$id (Đã Hủy) đã được xóa."));
                    exit();
                }
            }
            break;
        case "delete":
            if (isset($_GET["id"])) {
                $id = (int)$_GET["id"];
                if (rowCount("SELECT * FROM donhang WHERE id=? AND status=1", [$id]) > 0) {
                    exSQL("UPDATE donhang SET status=4 WHERE id=? AND status=1", [$id]);
                    header('Location: cart.php?success=' . urlencode("Đơn hàng #$id đã được hủy."));
                    exit();
                } elseif (rowCount("SELECT * FROM donhang WHERE id=? AND status=2", [$id]) > 0) {
                    exSQL("UPDATE donhang SET status=4 WHERE id=? AND status=2", [$id]);
                    header('Location: cart.php?success=' . urlencode("Đơn hàng #$id đã được hủy."));
                    exit();
                }
            }
            break;
    }
}

// Xử lý bộ lọc
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

$where_clauses = [];
$params = [];

// Lọc theo trạng thái
if ($status_filter !== '' && in_array($status_filter, ['0', '1', '2', '3', '4'])) {
    $where_clauses[] = "d.status = ?";
    $params[] = $status_filter;
}

// Lọc theo khoảng thời gian
if ($from_date !== '' && $to_date !== '') {
    $where_clauses[] = "d.thoigian BETWEEN ? AND ?";
    $params[] = $from_date;
    $params[] = $to_date . ' 23:59:59';
} elseif ($from_date !== '') {
    $where_clauses[] = "d.thoigian >= ?";
    $params[] = $from_date;
} elseif ($to_date !== '') {
    $where_clauses[] = "d.thoigian <= ?";
    $params[] = $to_date . ' 23:59:59';
}

// Lọc theo từ khóa
if ($keyword !== '') {
    $where_clauses[] = "(t.hoten LIKE ? OR t.taikhoan LIKE ? OR d.diachi LIKE ? OR t.sdt LIKE ?)";
    $keyword_like = '%' . $keyword . '%';
    $params[] = $keyword_like;
    $params[] = $keyword_like;
    $params[] = $keyword_like;
    $params[] = $keyword_like;
}

// Điều kiện mặc định: chỉ lấy đơn hàng hợp lệ (thoigian không NULL, tongtien > 0)
$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) . ' AND d.thoigian IS NOT NULL AND d.tongtien > 0' : 'WHERE d.status IN (0, 1, 2, 3, 4) AND d.thoigian IS NOT NULL AND d.tongtien > 0';

?>

<!-- Giao diện -->
<div class="main-panel">
    <div class="content-wrapper">
        <div class="row">
            <div class="col-12 grid-margin">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title addfont">Quản Lý Đơn Hàng</h4>
                        <!-- Hiển thị thông báo -->
                        <?php if (isset($_GET['success']) && !empty($_GET['success'])): ?>
                            <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
                        <?php endif; ?>
                        <?php if (isset($_GET['error']) && !empty($_GET['error'])): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($_GET['error']) ?></div>
                        <?php endif; ?>
                        <!-- Form lọc -->
                        <form method="GET" class="mb-4">
                            <div class="row">
                                <div class="col-md-3 form-group">
                                    <label for="status">Tình trạng</label>
                                    <select name="status" id="status" class="form-control">
                                        <option value="" <?= $status_filter == '' ? 'selected' : '' ?>>Tất cả</option>
                                        <option value="0" <?= $status_filter == '0' ? 'selected' : '' ?>>Chưa Xử Lý</option>
                                        <option value="1" <?= $status_filter == '1' ? 'selected' : '' ?>>Chờ Xác Nhận</option>
                                        <option value="2" <?= $status_filter == '2' ? 'selected' : '' ?>>Đang Giao</option>
                                        <option value="3" <?= $status_filter == '3' ? 'selected' : '' ?>>Đã Giao</option>
                                        <option value="4" <?= $status_filter == '4' ? 'selected' : '' ?>>Đã Hủy</option>
                                    </select>
                                </div>
                                <div class="col-md-3 form-group">
                                    <label for="from_date">Từ ngày</label>
                                    <input type="date" name="from_date" id="from_date" class="form-control" value="<?= htmlspecialchars($from_date) ?>">
                                </div>
                                <div class="col-md-3 form-group">
                                    <label for="to_date">Đến ngày</label>
                                    <input type="date" name="to_date" id="to_date" class="form-control" value="<?= htmlspecialchars($to_date) ?>">
                                </div>
                                <div class="col-md-3 form-group">
                                    <label for="keyword">Tìm kiếm</label>
                                    <input type="text" name="keyword" id="keyword" class="form-control" value="<?= htmlspecialchars($keyword) ?>" placeholder="Tìm theo tên, email, địa chỉ, SĐT">
                                </div>
                                <div class="col-md-3 form-group mt-4">
                                    <button type="submit" class="btn btn-primary">Lọc</button>
                                    <a href="cart.php" class="btn btn-secondary">Xóa bộ lọc</a>
                                </div>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <style>
                                .table th, .table td {
                                    vertical-align: middle;
                                    white-space: nowrap;
                                    overflow: hidden;
                                    text-overflow: ellipsis;
                                }
                                .table th:nth-child(1), .table td:nth-child(1) { width: 5%; }
                                .table th:nth-child(2), .table td:nth-child(2) { width: 15%; }
                                .table th:nth-child(3), .table td:nth-child(3) { width: 20%; }
                                .table th:nth-child(4), .table td:nth-child(4) { width: 10%; }
                                .table th:nth-child(5), .table td:nth-child(5) { width: 10%; }
                                .table th:nth-child(6), .table td:nth-child(6) { width: 15%; max-width: 150px; }
                                .table th:nth-child(7), .table td:nth-child(7) { width: 10%; }
                                .table th:nth-child(8), .table td:nth-child(8) { width: 25%; }
                            </style>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th class="addfont">STT</th>
                                        <th class="addfont">Khách Hàng</th>
                                        <th class="addfont">Tài khoản (Email)</th>
                                        <th class="addfont">ID Đơn Hàng</th>
                                        <th class="addfont">Tổng Tiền</th>
                                        <th class="addfont">Thời Gian Đặt Hàng</th>
                                        <th class="addfont">Trạng Thái</th>
                                        <th class="addfont">Chức Năng</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $stt = 1;
                                    $item_per_page = !empty($_GET['per_page']) ? (int)$_GET['per_page'] : 8;
                                    $current_page = !empty($_GET['page']) ? (int)$_GET['page'] : 1;
                                    $offset = ($current_page - 1) * $item_per_page;

                                    try {
                                        // Đếm tổng số đơn hàng hợp lệ
                                        $numrow = rowCount("SELECT d.* FROM donhang d LEFT JOIN taikhoan t ON d.id_taikhoan = t.id $where_sql", $params);
                                        echo "<div class='alert alert-info'>Số lượng đơn hàng tìm thấy: $numrow</div>";
                                        $totalpage = ceil($numrow / $item_per_page);

                                        // Lấy danh sách đơn hàng, sắp xếp theo thoigian DESC, sau đó id DESC
                                        $orders = selectAll("SELECT d.*, t.hoten, t.taikhoan, t.sdt FROM donhang d LEFT JOIN taikhoan t ON d.id_taikhoan = t.id $where_sql ORDER BY d.thoigian DESC, d.id DESC LIMIT $item_per_page OFFSET $offset", $params);
                                    } catch (PDOException $e) {
                                        echo "<tr><td colspan='8' class='text-center'>Lỗi truy vấn đơn hàng: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                                        error_log("Cart: Lỗi truy vấn đơn hàng: " . $e->getMessage());
                                        exit();
                                    }

                                    if (empty($orders)) {
                                        echo "<tr><td colspan='8' class='text-center'>Không có đơn hàng nào để hiển thị.</td></tr>";
                                    } else {
                                        foreach ($orders as $row) {
                                            if (!isset($row['id']) || !is_numeric($row['id'])) {
                                                $detail_link = "<span class='text-danger'>ID không hợp lệ</span>";
                                            } else {
                                                $detail_link = "<a type='button' class='btn btn-primary btn-icon-text' href='cartdetail.php?id=" . htmlspecialchars($row['id']) . "'><i class='mdi mdi-file-check btn-icon-prepend'></i>Chi Tiết</a>";
                                            }

                                            $status = match ((int)$row['status']) {
                                                0 => '<span class="badge badge-secondary">Chưa Xử Lý</span>',
                                                1 => '<span class="badge badge-info">Chờ Xác Nhận</span>',
                                                2 => '<span class="badge badge-warning">Đang Giao</span>',
                                                3 => '<span class="badge badge-success">Đã Giao</span>',
                                                4 => '<span class="badge badge-danger">Đã Hủy</span>',
                                                default => '<span class="badge badge-dark">Không xác định</span>',
                                            };

                                            $hoten = !empty($row['hoten']) ? htmlspecialchars($row['hoten']) : 'Không xác định';
                                            $taikhoan = !empty($row['taikhoan']) ? htmlspecialchars($row['taikhoan']) : 'Không xác định';
                                            $tongtien = number_format($row['tongtien'], 0, ',', '.') . 'đ';
                                            $thoigian = !empty($row['thoigian']) ? htmlspecialchars($row['thoigian']) : 'N/A';

                                            $action_buttons = '';
                                            if ($row['status'] == 1) {
                                                $action_buttons .= "<a type='button' class='btn btn-success btn-icon-text' href='cart.php?action=update&id=" . htmlspecialchars($row['id']) . "' onclick=\"return confirm('Bạn có muốn xác nhận đơn hàng này không?')\"><i class='mdi mdi-trending-up btn-icon-prepend'></i>Xác Nhận</a> ";
                                                $action_buttons .= "<a type='button' class='btn btn-danger btn-icon-text' href='cart.php?action=delete&id=" . htmlspecialchars($row['id']) . "' onclick=\"return confirm('Bạn có muốn hủy đơn hàng này không?')\"><i class='mdi mdi-delete btn-icon-prepend'></i>Hủy</a>";
                                            } elseif ($row['status'] == 2) {
                                                $action_buttons .= "<a type='button' class='btn btn-success btn-icon-text' href='cart.php?action=update&id=" . htmlspecialchars($row['id']) . "' onclick=\"return confirm('Bạn có muốn hoàn thành đơn hàng này không?')\"><i class='mdi mdi-trending-up btn-icon-prepend'></i>Hoàn Thành</a> ";
                                                $action_buttons .= "<a type='button' class='btn btn-danger btn-icon-text' href='cart.php?action=delete&id=" . htmlspecialchars($row['id']) . "' onclick=\"return confirm('Bạn có muốn hủy đơn hàng này không?')\"><i class='mdi mdi-delete btn-icon-prepend'></i>Hủy</a>";
                                            } elseif ($row['status'] == 4) {
                                                $action_buttons .= "<a type='button' class='btn btn-danger btn-icon-text' href='cart.php?action=update&id=" . htmlspecialchars($row['id']) . "' onclick=\"return confirm('Bạn có muốn xóa đơn hàng này không?')\"><i class='mdi mdi-delete btn-icon-prepend'></i>Xóa</a>";
                                            }

                                            echo "<tr>
                                                <td>$stt</td>
                                                <td>$hoten</td>
                                                <td>$taikhoan</td>
                                                <td>" . htmlspecialchars($row['id']) . "</td>
                                                <td>$tongtien</td>
                                                <td>$thoigian</td>
                                                <td>$status</td>
                                                <td>$detail_link $action_buttons</td>
                                            </tr>";
                                            $stt++;
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Phân trang -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <nav aria-label="Page navigation">
                                    <ul class="pagination justify-content-center">
                                        <?php if ($totalpage > 1): ?>
                                            <?php if ($current_page > 1): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="cart.php?page=<?= $current_page - 1 ?>&per_page=<?= $item_per_page ?>&status=<?= htmlspecialchars($status_filter) ?>&from_date=<?= htmlspecialchars($from_date) ?>&to_date=<?= htmlspecialchars($to_date) ?>&keyword=<?= htmlspecialchars($keyword) ?>" aria-label="Previous">
                                                        <span aria-hidden="true">«</span>
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                            <?php for ($i = 1; $i <= $totalpage; $i++): ?>
                                                <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                                                    <a class="page-link" href="cart.php?page=<?= $i ?>&per_page=<?= $item_per_page ?>&status=<?= htmlspecialchars($status_filter) ?>&from_date=<?= htmlspecialchars($from_date) ?>&to_date=<?= htmlspecialchars($to_date) ?>&keyword=<?= htmlspecialchars($keyword) ?>"><?= $i ?></a>
                                                </li>
                                            <?php endfor; ?>
                                            <?php if ($current_page < $totalpage): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="cart.php?page=<?= $current_page + 1 ?>&per_page=<?= $item_per_page ?>&status=<?= htmlspecialchars($status_filter) ?>&from_date=<?= htmlspecialchars($from_date) ?>&to_date=<?= htmlspecialchars($to_date) ?>&keyword=<?= htmlspecialchars($keyword) ?>" aria-label="Next">
                                                        <span aria-hidden="true">»</span>
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include_once 'footer.php'; ?>
</div>