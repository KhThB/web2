<?php
include 'header.php';

if (!isset($_COOKIE["user"])) {
    header("Location: ../login.php");
    exit();
}

$user = $_COOKIE["user"];
try {
    $taikhoan_rows = selectAll("SELECT * FROM taikhoan WHERE taikhoan=?", [$user]);
} catch (PDOException $e) {
    echo "Lỗi truy vấn taikhoan (user): " . htmlspecialchars($e->getMessage());
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
    echo "<div class='container mt-5'><h2>Tham số ID không tồn tại!</h2><p>Vui lòng truy cập từ danh sách đơn hàng.</p><a href='cart.php' class='btn btn-primary'>Quay lại</a></div>";
    include 'footer.php';
    exit();
}

if (!is_numeric($_GET["id"])) {
    echo "<div class='container mt-5'><h2>ID đơn hàng không phải là số! Giá trị nhận được: " . htmlspecialchars($_GET["id"]) . "</h2><p>Vui lòng kiểm tra dữ liệu trong bảng donhang.</p><a href='cart.php' class='btn btn-primary'>Quay lại</a></div>";
    include 'footer.php';
    exit();
}

$order_id = (int)$_GET["id"];
try {
    $order = selectAll("SELECT * FROM donhang WHERE id=?", [$order_id]);
} catch (PDOException $e) {
    echo "Lỗi truy vấn donhang: " . htmlspecialchars($e->getMessage());
    exit();
}

if (empty($order)) {
    echo "<div class='container mt-5'><h2>Đơn hàng không tồn tại! ID: " . htmlspecialchars($order_id) . "</h2><a href='cart.php' class='btn btn-primary'>Quay lại</a></div>";
    include 'footer.php';
    exit();
}

$items = $order[0];
$id_donhang = $items['id'];
$id_taikhoan = $items['id_taikhoan'];
$diachi = $items['diachi'] ?? 'Không có địa chỉ';
$tongtien = $items['tongtien'] ?? 0;
$status = $items['status'] ?? 0;

// Lấy thông tin từ taikhoan
try {
    $taikhoan_rows = selectAll("SELECT * FROM taikhoan WHERE id=?", [$id_taikhoan]);
} catch (PDOException $e) {
    echo "Lỗi truy vấn taikhoan (id_taikhoan): " . htmlspecialchars($e->getMessage());
    exit();
}

$hoten = !empty($taikhoan_rows) ? ($taikhoan_rows[0]['hoten'] ?? 'Khách hàng không xác định') : 'Khách hàng không xác định';
$sdt = !empty($taikhoan_rows) ? ($taikhoan_rows[0]['sdt'] ?? 'Không có') : 'Không có';
$taikhoan = !empty($taikhoan_rows) ? ($taikhoan_rows[0]['taikhoan'] ?? 'Không xác định') : 'Không xác định';
?>

<div class="main-panel">
    <div class="content-wrapper">
        <div class="row">
            <div class="col-12 grid-margin">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title addfont">Chi Tiết Đơn Hàng</h4>
                        <div class="d-flex addfont">
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="exampleInputName1">ID Đơn Hàng</label>
                                    <input type="text" name="ten" value="<?= htmlspecialchars($id_donhang) ?>" class="form-control text-light" >
                                </div>
                                <div class="form-group">
                                    <label for="exampleInputName1">Tổng Tiền</label>
                                    <input type="text" name="email" value="<?= number_format($tongtien) ?>đ" class="form-control text-light" >
                                </div>
                                <div class="form-group">
                                    <label for="exampleInputName1">Trạng Thái</label>
                                    <input type="text" value="<?php
                                        if ($status == 1) echo 'Chờ Xác Nhận';
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
                                    <input type="text" name="ten" value="<?= htmlspecialchars($hoten) ?>" class="form-control text-light" >
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
                                    <input type="text" name="email" value="<?= htmlspecialchars($taikhoan) ?>" class="form-control text-light" >
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
                                        echo "Lỗi truy vấn rowCount ctdonhang: " . htmlspecialchars($e->getMessage());
                                        exit();
                                    }
                                    $totalpage = ceil($numrow / $item_per_page);
                                    try {
                                        $ctdonhang = selectAll("SELECT * FROM ctdonhang WHERE id_donhang=? LIMIT $item_per_page OFFSET $offset", [$order_id]);
                                    } catch (PDOException $e) {
                                        echo "Lỗi truy vấn ctdonhang: " . htmlspecialchars($e->getMessage());
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
                                            echo "Lỗi truy vấn sanpham: " . htmlspecialchars($e->getMessage());
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
                                                    <a type="button" class="btn btn-primary btn-icon-text" href="../detail.php?id=<?= $item4['id_sanpham'] ?>">
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