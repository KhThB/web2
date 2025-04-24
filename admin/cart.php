<?php
ob_start();
session_start();
include_once 'header.php';

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

$permission = $taikhoan_rows[0]['phanquyen'];
if ($permission != 1) {
    echo "<div class='container mt-5'><h2>Bạn không có quyền truy cập trang này!</h2><a href='../index.php' class='btn btn-primary'>Quay lại</a></div>";
    include_once 'footer.php';
    exit();
}

if (isset($_GET["action"])) {
    switch ($_GET["action"]) {
        case "update":
            if (isset($_GET["id"])) {
                $id = (int)$_GET["id"];
                if (rowCount("SELECT * FROM donhang WHERE id=? AND status=1", [$id]) > 0) {
                    exSQL("UPDATE donhang SET status=2 WHERE id=? AND status=1", [$id]);
                    header('Location: cart.php');
                    exit();
                } elseif (rowCount("SELECT * FROM donhang WHERE id=? AND status=2", [$id]) > 0) {
                    exSQL("UPDATE donhang SET status=3 WHERE id=? AND status=2", [$id]);
                    header('Location: cart.php');
                    exit();
                } elseif (rowCount("SELECT * FROM donhang WHERE id=? AND status=4", [$id]) > 0) {
                    exSQL("DELETE FROM ctdonhang WHERE id_donhang=?", [$id]);
                    exSQL("DELETE FROM donhang WHERE id=?", [$id]);
                    header('Location: cart.php');
                    exit();
                }
            }
            break;
        case "delete":
            if (isset($_GET["id"])) {
                $id = (int)$_GET["id"];
                if (rowCount("SELECT * FROM donhang WHERE id=? AND status=1", [$id]) > 0) {
                    exSQL("UPDATE donhang SET status=4 WHERE id=? AND status=1", [$id]);
                    header('Location: cart.php');
                    exit();
                } elseif (rowCount("SELECT * FROM donhang WHERE id=? AND status=2", [$id]) > 0) {
                    exSQL("UPDATE donhang SET status=4 WHERE id=? AND status=2", [$id]);
                    header('Location: cart.php');
                    exit();
                }
            }
            break;
    }
}

// Filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : ''; // Từ khóa tìm kiếm

$where_clauses = [];
$params = [];

if ($status_filter !== '' && in_array($status_filter, ['1', '2', '3', '4'])) {
    $where_clauses[] = "d.status = ?";
    $params[] = $status_filter;
}

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

// Tìm kiếm theo từ khóa trong hoten, taikhoan, diachi, và sdt
if ($keyword !== '') {
    $where_clauses[] = "(t.hoten LIKE ? OR t.taikhoan LIKE ? OR d.diachi LIKE ? OR t.sdt LIKE ?)";
    $keyword_like = '%' . $keyword . '%';
    $params[] = $keyword_like;
    $params[] = $keyword_like;
    $params[] = $keyword_like;
    $params[] = $keyword_like;
}

$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : 'WHERE d.status IN (1, 2, 3, 4)';
?>

<!-- partial -->
<div class="main-panel">
    <div class="content-wrapper">
        <div class="row">
            <div class="col-12 grid-margin">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title addfont">Quản Lý Đơn Hàng</h4>
                        <!-- Filter Form -->
                        <form method="GET" class="mb-4">
                            <div class="row">
                                <div class="col-md-3 form-group">
                                    <label for="status">Tình trạng</label>
                                    <select name="status" id="status" class="form-control">
                                        <option value="" <?= $status_filter == '' ? 'selected' : '' ?>>Tất cả</option>
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
                                /* CSS to control table column widths and text overflow */
                                .table th, .table td {
                                    vertical-align: middle;
                                    white-space: nowrap;
                                    overflow: hidden;
                                    text-overflow: ellipsis;
                                }
                                .table th:nth-child(1), .table td:nth-child(1) { /* STT */
                                    width: 5%;
                                }
                                .table th:nth-child(2), .table td:nth-child(2) { /* Khách Hàng */
                                    width: 15%;
                                }
                                .table th:nth-child(3), .table td:nth-child(3) { /* Tài khoản (Email) */
                                    width: 20%;
                                }
                                .table th:nth-child(4), .table td:nth-child(4) { /* ID Đơn Hàng */
                                    width: 10%;
                                }
                                .table th:nth-child(5), .table td:nth-child(5) { /* Tổng Tiền */
                                    width: 10%;
                                }
                                .table th:nth-child(6), .table td:nth-child(6) { /* Thời Gian Đặt Hàng */
                                    width: 15%;
                                    max-width: 150px;
                                }
                                .table th:nth-child(7), .table td:nth-child(7) { /* Trạng Thái */
                                    width: 10%;
                                }
                                .table th:nth-child(8), .table td:nth-child(8) { /* Chức Năng */
                                    width: 25%;
                                }
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
                                        $numrow = rowCount("SELECT d.* FROM donhang d JOIN taikhoan t ON d.id_taikhoan = t.id $where_sql", $params);
                                        $totalpage = ceil($numrow / $item_per_page);
                                        $orders = selectAll("SELECT d.* FROM donhang d JOIN taikhoan t ON d.id_taikhoan = t.id $where_sql ORDER BY d.thoigian DESC LIMIT $item_per_page OFFSET $offset", $params);
                                    } catch (PDOException $e) {
                                        echo "<tr><td colspan='8' class='text-center'>Lỗi truy vấn đơn hàng: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                                        exit();
                                    }

                                    if (empty($orders)) {
                                        echo "<tr><td colspan='8' class='text-center'>Không có đơn hàng nào để hiển thị.</td></tr>";
                                    } else {
                                        foreach ($orders as $row) {
                                            if (!isset($row['id']) || !is_numeric($row['id'])) {
                                                $detail_link = "<span class='text-danger'>ID không hợp lệ</span>";
                                            } else {
                                                $detail_link = "<a type='button' class='btn btn-primary btn-icon-text' href='cartdetail.php?id=" . htmlspecialchars($row['id']) . "'>
                                                                   <i class='mdi mdi-file-check btn-icon-prepend'></i> Chi Tiết
                                                                </a>";
                                            }
                                    ?>
                                            <tr class="addfont">
                                                <td><?= $stt++ ?></td>
                                                <td>
                                                    <?php
                                                    try {
                                                        $taikhoan_rows = selectAll("SELECT * FROM taikhoan WHERE id=?", [$row['id_taikhoan']]);
                                                        if (!empty($taikhoan_rows)) {
                                                            $hoten = htmlspecialchars($taikhoan_rows[0]['hoten'] ?? 'Không xác định');
                                                            $taikhoan = htmlspecialchars($taikhoan_rows[0]['taikhoan'] ?? 'Không xác định');
                                                            $sdt = htmlspecialchars($taikhoan_rows[0]['sdt'] ?? 'Không có');
                                                        } else {
                                                            $hoten = "Không xác định";
                                                            $taikhoan = "Không xác định";
                                                            $sdt = "Không có";
                                                        }
                                                    } catch (PDOException $e) {
                                                        $hoten = "Lỗi truy vấn";
                                                        $taikhoan = "Lỗi truy vấn";
                                                        $sdt = "Lỗi truy vấn";
                                                        echo "<tr><td colspan='8'>Lỗi truy vấn tài khoản: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                                                    }
                                                    $diachi = htmlspecialchars($row['diachi'] ?? 'Không có địa chỉ');
                                                    ?>
                                                    <span><?= $hoten ?></span>
                                                </td>
                                                <td>
                                                    <span><?= $taikhoan ?></span>
                                                </td>
                                                <td><?= isset($row['id']) ? htmlspecialchars($row['id']) : 'N/A' ?></td>
                                                <td><?= isset($row['tongtien']) ? number_format($row['tongtien']) . 'đ' : '0đ' ?></td>
                                                <td>
                                                    <p class="addfont"><?= htmlspecialchars($row['thoigian'] ?? 'N/A') ?></p>
                                                </td>
                                                <td>
                                                    <?php
                                                    $status = $row['status'] ?? 0;
                                                    if ($status == 1) {
                                                        echo '<span class="badge badge-info">Chờ Xác Nhận</span>';
                                                    } elseif ($status == 2) {
                                                        echo '<span class="badge badge-warning">Đang Giao</span>';
                                                    } elseif ($status == 3) {
                                                        echo '<span class="badge badge-success">Đã Giao</span>';
                                                    } else {
                                                        echo '<span class="badge badge-danger">Đã Hủy</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?= $detail_link ?>
                                                    <?php if ($status == 1) { ?>
                                                        <a type="button" class="btn btn-success btn-icon-text" href="?action=update&id=<?= $row['id'] ?>" onclick="return confirm('Bạn có muốn xác nhận đơn hàng này không?')">
                                                            <i class="mdi mdi-trending-up btn-icon-prepend"></i> Xác Nhận
                                                        </a>
                                                        <a type="button" class="btn btn-danger btn-icon-text" href="?action=delete&id=<?= $row['id'] ?>" onclick="return confirm('Bạn có muốn hủy đơn hàng này không?')">
                                                            <i class="mdi mdi-delete btn-icon-prepend"></i> Hủy
                                                        </a>
                                                    <?php } elseif ($status == 2) { ?>
                                                        <a type="button" class="btn btn-success btn-icon-text" href="?action=update&id=<?= $row['id'] ?>" onclick="return confirm('Bạn có muốn hoàn thành đơn hàng này không?')">
                                                            <i class="mdi mdi-trending-up btn-icon-prepend"></i> Hoàn Thành
                                                        </a>
                                                        <a type="button" class="btn btn-danger btn-icon-text" href="?action=delete&id=<?= $row['id'] ?>" onclick="return confirm('Bạn có muốn hủy đơn hàng này không?')">
                                                            <i class="mdi mdi-delete btn-icon-prepend"></i> Hủy
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
                                                        <li class="page-item"><a class="btn btn-outline-secondary" href="?per_page=<?= $item_per_page ?>&page=<?= $num ?>&status=<?= urlencode($status_filter) ?>&from_date=<?= urlencode($from_date) ?>&to_date=<?= urlencode($to_date) ?>&keyword=<?= urlencode($keyword) ?>"><?= $num ?></a></li>
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
include_once 'footer.php';
?>