<?php
session_start();
include 'header.php';

// Đảm bảo session được khởi tạo
if (session_status() !== PHP_SESSION_ACTIVE) {
    error_log("Cartdetail: Session không hoạt động, khởi tạo lại");
    session_start();
}

if (!isset($_COOKIE["user"])) {
    header("Location: ../login.php");
    exit();
}

$user = $_COOKIE["user"];
try {
    $taikhoan_rows = selectAll("SELECT * FROM taikhoan WHERE taikhoan=?", [$user]);
} catch (PDOException $e) {
    echo "<div class='container mt-5'><h2>Lỗi truy vấn tài khoản: " . htmlspecialchars($e->getMessage()) . "</h2><a href='../index.php' class='btn btn-primary'>Quay lại</a></div>";
    include 'footer.php';
    exit();
}

if (empty($taikhoan_rows)) {
    header("Location: ../login.php");
    exit();
}

$permission = $taikhoan_rows[0]['phanquyen'];
if ($permission != 1) {
    echo "<div class='container mt-5'><h2>Bạn không có quyền truy cập trang này!</h2><a href='../index.php' class='btn btn-primary'>Quay lại</a></div>";
    include 'footer.php';
    exit();
}

// Kiểm tra tham số id
if (!isset($_GET["id"])) {
    echo "<div class='container mt-5'><h2>Tham số ID không tồn tại!</h2><p>Vui lòng truy cập từ danh sách đơn hàng.</p><a href='cart.php' class='btn btn-primary'>Quay lại</a>";
    if (isset($_SESSION['latest_order_id'])) {
        echo "<p>Đề xuất: Xem đơn hàng mới nhất với ID: <a href='cartdetail.php?id=" . htmlspecialchars($_SESSION['latest_order_id']) . "' class='btn btn-secondary'>#" . htmlspecialchars($_SESSION['latest_order_id']) . "</a></p>";
    }
    include 'footer.php';
    exit();
}

if (!is_numeric($_GET["id"])) {
    echo "<div class='container mt-5'><h2>ID đơn hàng không phải là số! Giá trị nhận được: " . htmlspecialchars($_GET["id"]) . "</h2><p>Vui lòng kiểm tra dữ liệu trong bảng donhang.</p><a href='cart.php' class='btn btn-primary'>Quay lại</a>";
    if (isset($_SESSION['latest_order_id'])) {
        echo "<p>Đề xuất: Xem đơn hàng mới nhất với ID: <a href='cartdetail.php?id=" . htmlspecialchars($_SESSION['latest_order_id']) . "' class='btn btn-secondary'>#" . htmlspecialchars($_SESSION['latest_order_id']) . "</a></p>";
    }
    include 'footer.php';
    exit();
}

$order_id = (int)$_GET["id"];
error_log("Cartdetail: Truy cập với order_id = $order_id");
error_log("Cartdetail: Debug SESSION - latest_order_id: " . ($_SESSION['latest_order_id'] ?? 'Không có'));

// Kiểm tra xem order_id có khớp với đơn hàng mới nhất trong SESSION không
if (isset($_SESSION['latest_order_id']) && $order_id != $_SESSION['latest_order_id']) {
    error_log("Cartdetail: Cảnh báo - order_id ($order_id) không khớp với latest_order_id trong SESSION ({$_SESSION['latest_order_id']})");
    $warning_message = "Cảnh báo: ID đơn hàng ($order_id) không khớp với đơn hàng mới nhất trong SESSION (#" . htmlspecialchars($_SESSION['latest_order_id']) . "). <a href='cartdetail.php?id=" . htmlspecialchars($_SESSION['latest_order_id']) . "' class='btn btn-warning'>Xem đơn hàng mới nhất</a>";
} elseif (!isset($_SESSION['latest_order_id'])) {
    error_log("Cartdetail: Không có latest_order_id trong SESSION");
}

// Truy vấn đơn hàng mà không cần kiểm tra trạng thái tài khoản
try {
    $order = selectAll("SELECT * FROM donhang WHERE id=?", [$order_id]);
} catch (PDOException $e) {
    echo "<div class='container mt-5'><h2>Lỗi truy vấn đơn hàng: " . htmlspecialchars($e->getMessage()) . "</h2><a href='cart.php' class='btn btn-primary'>Quay lại</a></div>";
    include 'footer.php';
    exit();
}

if (empty($order)) {
    echo "<div class='container mt-5'><h2>Đơn hàng không tồn tại hoặc không hợp lệ! ID: " . htmlspecialchars($order_id) . "</h2><p>Vui lòng kiểm tra danh sách đơn hàng.</p><a href='cart.php' class='btn btn-primary'>Quay lại</a>";
    if (isset($_SESSION['latest_order_id'])) {
        echo "<p>Đề xuất: Xem đơn hàng mới nhất với ID: <a href='cartdetail.php?id=" . htmlspecialchars($_SESSION['latest_order_id']) . "' class='btn btn-secondary'>#" . htmlspecialchars($_SESSION['latest_order_id']) . "</a></p>";
    }
    include 'footer.php';
    exit();
}

$items = $order[0];
$id_donhang = $items['id'];
$id_taikhoan = $items['id_taikhoan'];
$tongtien = $items['tongtien'] ?? 0;
$status = $items['status'] ?? 0;

// Kiểm tra tính nhất quán của ID
if ($id_donhang != $order_id) {
    error_log("Cartdetail: ID không nhất quán - order_id từ GET: $order_id, id_donhang từ cơ sở dữ liệu: $id_donhang");
    $warning_message = "Lỗi: ID đơn hàng không nhất quán. GET ID: $order_id, Cơ sở dữ liệu ID: $id_donhang. Vui lòng kiểm tra.";
}

// Lấy thông tin từ taikhoan
try {
    $taikhoan_rows = selectAll("SELECT * FROM taikhoan WHERE id=?", [$id_taikhoan]);
} catch (PDOException $e) {
    echo "<div class='container mt-5'><h2>Lỗi truy vấn tài khoản: " . htmlspecialchars($e->getMessage()) . "</h2><a href='cart.php' class='btn btn-primary'>Quay lại</a></div>";
    include 'footer.php';
    exit();
}

$taikhoan = !empty($taikhoan_rows) ? ($taikhoan_rows[0]['taikhoan'] ?? 'Không xác định') : 'Không xác định';

// Khởi tạo thông tin từ donhang
$hoten = $items['hoten'] ?? 'Khách hàng không xác định';
$sdt = $items['sdt'] ?? 'Không có';
$diachi = $items['diachi'] ?? 'Không có địa chỉ';
$data_source = 'Cơ sở dữ liệu (donhang)';

// Log để so sánh dữ liệu
error_log("Cartdetail: Dữ liệu từ donhang ID $id_donhang - hoten: '$hoten', sdt: '$sdt', diachi: '$diachi'");
if (isset($_SESSION['order_info_list'][$id_donhang])) {
    error_log("Cartdetail: Dữ liệu từ SESSION - hoten: '{$_SESSION['order_info_list'][$id_donhang]['hoten']}', sdt: '{$_SESSION['order_info_list'][$id_donhang]['sdt']}', diachi: '{$_SESSION['order_info_list'][$id_donhang]['diachi']}'");
} else {
    error_log("Cartdetail: Không có SESSION order_info_list cho donhang_id $id_donhang");
}
if (isset($_COOKIE['order_info_list'])) {
    $order_info_list = json_decode($_COOKIE['order_info_list'], true);
    if (json_last_error() === JSON_ERROR_NONE && isset($order_info_list[$id_donhang])) {
        error_log("Cartdetail: Dữ liệu từ COOKIE - hoten: '{$order_info_list[$id_donhang]['hoten']}', sdt: '{$order_info_list[$id_donhang]['sdt']}', diachi: '{$order_info_list[$id_donhang]['diachi']}'");
    } else {
        error_log("Cartdetail: Lỗi giải mã COOKIE hoặc không có dữ liệu cho donhang_id $id_donhang: " . json_last_error_msg());
    }
} else {
    error_log("Cartdetail: Không có COOKIE order_info_list");
}
?>

<div class="main-panel">
    <div class="content-wrapper">
        <div class="row">
            <div class="col-12 grid-margin">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title addfont">Chi Tiết Đơn Hàng</h4>
                        <?php if (isset($warning_message)): ?>
                            <div class="alert alert-warning"><?= $warning_message ?></div>
                        <?php endif; ?>
                        <p><strong>Nguồn dữ liệu:</strong> <?= htmlspecialchars($data_source) ?></p>
                        <div class="d-flex addfont">
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="exampleInputName1">ID Đơn Hàng</label>
                                    <input type="text" name="id_donhang" value="<?= htmlspecialchars($id_donhang) ?>" class="form-control text-light" >
                                </div>
                                <div class="form-group">
                                    <label for="exampleInputName1">Tổng Tiền</label>
                                    <input type="text" name="tongtien" value="<?= number_format($tongtien) ?>đ" class="form-control text-light" >
                                </div>
                                <div class="form-group">
                                    <label for="exampleInputName1">Trạng Thái</label>
                                    <input type="text" value="<?php
                                        if ($status == 0) echo 'Chưa Xử Lý';
                                        elseif ($status == 1) echo 'Chờ Xác Nhận';
                                        elseif ($status == 2) echo 'Đang Giao';
                                        elseif ($status == 3) echo 'Đã Giao';
                                        elseif ($status == 4) echo 'Đã Hủy';
                                        else echo 'Không xác định';
                                    ?>" class="form-control text-light" >
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="exampleInputName1">Tên Khách Hàng</label>
                                    <input type="text" name="hoten" value="<?= htmlspecialchars($hoten) ?>" class="form-control text-light" >
                                </div>
                                <div class="form-group">
                                    <label for="exampleInputName1">Số Điện Thoại</label>
                                    <input type="text" name="sdt" value="<?= htmlspecialchars($sdt) ?>" class="form-control text-light" >
                                </div>
                                <div class="form-group">
                                    <label for="exampleInputName1">Địa Chỉ Nhận Hàng</label>
                                    <input type="text" name="diachi" value="<?= htmlspecialchars($diachi) ?>" class="form-control text-light" >
                                </div>
                                <div class="form-group">
                                    <label for="exampleInputName1">Tài Khoản Khách Hàng</label>
                                    <input type="text" name="taikhoan" value="<?= htmlspecialchars($taikhoan) ?>" class="form-control text-light" >
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th class="addfont" style="width: 20px">STT</th>
                                        <th class="addfont" style="width: 400px">Tên Sản Phẩm</th>
                                        <th class="addfont">Danh Mục</th>
                                        <th class="addfont">Giá</th>
                                        <th class="addfont">Ảnh Sản Phẩm</th>
                                        <th class="addfont">Số Lượng</th>
                                        <th class="addfont">Xem chi tiết</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $stt = 1;
                                    $item_per_page = !empty($_GET['per_page']) ? (int)$_GET['per_page'] : 4;
                                    $current_page = !empty($_GET['page']) ? (int)$_GET['page'] : 1;
                                    $offset = ($current_page - 1) * $item_per_page;
                                    try {
                                        $numrow = rowCount("SELECT * FROM ctdonhang WHERE id_donhang=?", [$order_id]);
                                    } catch (PDOException $e) {
                                        echo "<div class='container mt-5'><h2>Lỗi truy vấn chi tiết đơn hàng: " . htmlspecialchars($e->getMessage()) . "</h2><a href='cart.php' class='btn btn-primary'>Quay lại</a></div>";
                                        include 'footer.php';
                                        exit();
                                    }
                                    $totalpage = ceil($numrow / $item_per_page);
                                    try {
                                        $ctdonhang = selectAll("SELECT * FROM ctdonhang WHERE id_donhang=? LIMIT $item_per_page OFFSET $offset", [$order_id]);
                                    } catch (PDOException $e) {
                                        echo "<div class='container mt-5'><h2>Lỗi truy vấn chi tiết đơn hàng: " . htmlspecialchars($e->getMessage()) . "</h2><a href='cart.php' class='btn btn-primary'>Quay lại</a></div>";
                                        include 'footer.php';
                                        exit();
                                    }
                                    foreach ($ctdonhang as $item4) {
                                        $id_sanpham = $item4['id_sanpham'];
                                        $soluong = $item4['soluong'];
                                        $gia = $item4['gia'];
                                        try {
                                            $sanpham = selectAll("SELECT sanpham.ten, sanpham.anh1, sanpham.anh2, sanpham.anh3, danhmuc.danhmuc 
                                                                  FROM sanpham 
                                                                  INNER JOIN danhmuc ON sanpham.id_danhmuc = danhmuc.id_dm 
                                                                  WHERE sanpham.id=?", [$id_sanpham]);
                                        } catch (PDOException $e) {
                                            echo "<div class='container mt-5'><h2>Lỗi truy vấn sản phẩm: " . htmlspecialchars($e->getMessage()) . "</h2><a href='cart.php' class='btn btn-primary'>Quay lại</a></div>";
                                            include 'footer.php';
                                            exit();
                                        }
                                        foreach ($sanpham as $row) {
                                    ?>
                                            <tr class="addfont">
                                                <td><?= $stt++ ?></td>
                                                <td>
                                                    <span><?= htmlspecialchars($row['ten']) ?></span>
                                                </td>
                                                <td>
                                                    <?= htmlspecialchars($row['danhmuc']) ?>
                                                </td>
                                                <td><?= number_format($gia) ?>đ</td>
                                                <td>
                                                    <img src="../img/product/<?= htmlspecialchars($row['anh1']) ?>" width="100" alt="">
                                                    <img src="../img/product/<?= htmlspecialchars($row['anh2']) ?>" width="100" alt="">
                                                    <img src="../img/product/<?= htmlspecialchars($row['anh3']) ?>" width="100" alt="">
                                                </td>
                                                <td><?= $soluong ?></td>
                                                <td>
                                                    <a type="button" class="btn btn-primary btn-icon-text" href="../detail.php?id=<?= htmlspecialchars($item4['id_sanpham']) ?>">
                                                        <i class="mdi mdi-file-check btn-icon-prepend"></i> Xem
                                                    </a>
                                                </td>
                                            </tr>
                                    <?php
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>

                            <div class="col-lg-12">
                                <div class="pageination">
                                    <nav aria-label="Page navigation example">
                                        <ul class="pagination justify-content-center">
                                            <?php for ($num = 1; $num <= $totalpage; $num++) { ?>
                                                <?php if ($num != $current_page) { ?>
                                                    <?php if ($num > $current_page - 3 && $num < $current_page + 3) { ?>
                                                        <li class="page-item"><a class="btn btn-outline-secondary" href="?id=<?= $order_id ?>&per_page=<?= $item_per_page ?>&page=<?= $num ?>"><?= $num ?></a></li>
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
        <script src="./js/search.js?v=<?php echo time() ?>"></script>
<?php
include 'footer.php';
?>