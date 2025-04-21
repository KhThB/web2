<?php
// Không include header.php và footer.php vì đã xử lý trong index.php
$tongtienhientai = 0;
$tongtiendutinh = 0;
$tongtiengiam = 0;

// Kiểm tra cookie user và quyền admin
if (isset($_COOKIE["user"])) {
    $user = $_COOKIE["user"];
    $sql = "SELECT * FROM taikhoan WHERE taikhoan = ?";
    try {
        $result = selectAll($sql, [$user]);
        $permission = 0;
        foreach ($result as $row) {
            $permission = $row['phanquyen'];
        }
        if ($permission == 1) {
            // Tính tổng doanh thu thực tế (status = 3)
            foreach (selectAll("SELECT SUM(tongtien) as total FROM donhang WHERE status = 3", []) as $item) {
                $tongtienhientai = $item['total'] ?? 0;
            }
            // Tính tổng doanh thu dự tính (status = 1, 2, 3)
            foreach (selectAll("SELECT SUM(tongtien) as total FROM donhang WHERE status IN (1, 2, 3)", []) as $item) {
                $tongtiendutinh = $item['total'] ?? 0;
            }
            // Tính tổng khoản giảm trừ (status = 4)
            foreach (selectAll("SELECT SUM(tongtien) as total FROM donhang WHERE status = 4", []) as $item) {
                $tongtiengiam = $item['total'] ?? 0;
            }

            // Xử lý form thống kê top 5 khách hàng
            $from_date = isset($_POST['from_date']) ? $_POST['from_date'] : '';
            $to_date = isset($_POST['to_date']) ? $_POST['to_date'] : '';
            $top_customers = [];
            $debug_message = ''; // Biến debug

            if ($from_date && $to_date) {
                // Chuyển định dạng thời gian sang DD-MM-YYYY HH:MM:SS
                $from_date_sql = date('d-m-Y 00:00:00', strtotime($from_date));
                $to_date_sql = date('d-m-Y 23:59:59', strtotime($to_date));

                // Truy vấn top 5 khách hàng
                $sql_top5 = "SELECT tk.id, tk.hoten, tk.taikhoan, tk.anh, 
                                COUNT(dh.id) as so_don, 
                                SUM(dh.tongtien) as tong_tien
                             FROM taikhoan tk
                             LEFT JOIN donhang dh ON tk.id = dh.id_taikhoan 
                             WHERE dh.status = 3 
                             AND dh.thoigian BETWEEN ? AND ?
                             GROUP BY tk.id
                             ORDER BY tong_tien DESC
                             LIMIT 5";
                $top_customers = selectAll($sql_top5, [$from_date_sql, $to_date_sql]);

                // Debug: Kiểm tra số lượng khách hàng
                $debug_message = "Số khách hàng tìm thấy: " . count($top_customers);
            } else {
                $debug_message = "Chưa nhập khoảng thời gian.";
            }

            // Đặt khoảng thời gian mặc định cho bảng top 5 tài khoản nếu không có from_date/to_date
            $default_from_date = $from_date ?: '2000-01-01';
            $default_to_date = $to_date ?: date('Y-m-d');
?>
<!-- Nội dung giao diện -->
<div class="main-panel">
    <div class="content-wrapper">
        <!-- Debug message (ẩn trong sản phẩm thật) -->
        <p style="color: red; display: none;">Debug: <?= htmlspecialchars($debug_message) ?></p>

        <div class="row">
            <div class="col-sm-4 grid-margin">
                <div class="card">
                    <div class="card-body">
                        <h5 class="addfont">Tổng Doanh Thu Thực Tế</h5>
                        <div class="row">
                            <div class="col-8 col-sm-12 col-xl-8 my-auto">
                                <div class="d-flex d-sm-block d-md-flex align-items-center">
                                    <h2 class="mb-0"><?= number_format($tongtienhientai) ?>đ</h2>
                                </div>
                            </div>
                            <div class="col-4 col-sm-12 col-xl-4 text-center text-xl-right">
                                <i class="icon-lg mdi mdi-monitor text-success ml-auto"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-4 grid-margin">
                <div class="card">
                    <div class="card-body">
                        <h5 class="addfont">Tổng Doanh Thu Dự Tính</h5>
                        <div class="row">
                            <div class="col-8 col-sm-12 col-xl-8 my-auto">
                                <div class="d-flex d-sm-block d-md-flex align-items-center">
                                    <h2 class="mb-0"><?= number_format($tongtiendutinh) ?>đ</h2>
                                </div>
                            </div>
                            <div class="col-4 col-sm-12 col-xl-4 text-center text-xl-right">
                                <i class="icon-lg mdi mdi-codepen text-primary ml-auto"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-4 grid-margin">
                <div class="card">
                    <div class="card-body">
                        <h5 class="addfont">Tổng Các Khoản Giảm Trừ Doanh Thu</h5>
                        <div class="row">
                            <div class="col-8 col-sm-12 col-xl-8 my-auto">
                                <div class="d-flex d-sm-block d-md-flex align-items-center">
                                    <h2 class="mb-0"><?= number_format($tongtiengiam) ?>đ</h2>
                                </div>
                            </div>
                            <div class="col-4 col-sm-12 col-xl-4 text-center text-xl-right">
                                <i class="icon-lg mdi mdi-wallet-travel text-danger ml-auto"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Thống kê top 5 khách hàng -->
            <div class="col-12 grid-margin">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title addfont">Thống Kê Top 5 Khách Hàng Mua Nhiều Nhất Trong Khoảng Thời Gian </h4>
                        <!-- Form nhập khoảng thời gian -->
                        <form method="POST" action="index.php" class="form-inline mb-4">
                            <div class="form-group mr-2">
                                <label for="from_date" class="mr-2">Từ ngày:</label>
                                <input type="date" name="from_date" id="from_date" class="form-control" value="<?= htmlspecialchars($from_date) ?>" required>
                            </div>
                            <div class="form-group mr-2">
                                <label for="to_date" class="mr-2">Đến ngày:</label>
                                <input type="date" name="to_date" id="to_date" class="form-control" value="<?= htmlspecialchars($to_date) ?>" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Thống kê</button>
                        </form>

                        <!-- Bảng hiển thị top 5 khách hàng -->
                        <?php if (!empty($top_customers)) { ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th class="addfont" style="width: 100px">STT</th>
                                        <th class="addfont" style="width: 500px">Họ Tên</th>
                                        <th class="addfont" style="width: 400px">Tài Khoản (Email)</th>
                                        <th class="addfont" style="width: 300px">Số Đơn Đã Mua</th>
                                        <th class="addfont" style="width: 300px">Tổng Tiền</th>
                                        <th class="addfont" style="width: 200px">Chi Tiết</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $stt = 1;
                                    foreach ($top_customers as $customer) {
                                        $tong_tien = $customer['tong_tien'] ?? 0;
                                    ?>
                                        <tr class="addfont">
                                            <td><?= $stt++ ?></td>
                                            <td>
                                                <img src="<?= empty($customer['anh']) ? '../img/account/user.png' : '../img/account/' . htmlspecialchars($customer['anh']) ?>" alt="image">
                                                <span><?= htmlspecialchars($customer['hoten']) ?></span>
                                            </td>
                                            <td><?= htmlspecialchars($customer['taikhoan']) ?></td>
                                            <td><?= $customer['so_don'] ?></td>
                                            <td><?= number_format($tong_tien) ?>đ</td>
                                            <td>
                                                <a type="button" class="btn btn-primary btn-icon-text" href="user_orders.php?from_date=<?= urlencode($from_date) ?>&to_date=<?= urlencode($to_date) ?>&customer_id=<?= $customer['id'] ?>">
                                                    <i class="mdi mdi-file-check btn-icon-prepend"></i> Chi Tiết
                                                </a>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                        <?php } else { ?>
                            <p class="addfont">Vui lòng chọn khoảng thời gian để thống kê top 5 khách hàng.</p>
                        <?php } ?>
                    </div>
                </div>
            </div>

            <!-- Top 5 tài khoản có tổng tiền mua cao nhất -->
            <div class="col-12 grid-margin">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title addfont">Top 5 Tài Khoản Mua Nhiều Nhất Từ Khi Bán Hàng</h4>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th class="addfont" style="width: 100px">STT</th>
                                        <th class="addfont" style="width: 500px">Họ Tên</th>
                                        <th class="addfont" style="width: 400px">Tài Khoản (Email)</th>
                                        <th class="addfont" style="width: 300px">Loại Tài Khoản</th>
                                        <th class="addfont" style="width: 300px">Số Đơn Đã Mua</th>
                                        <th class="addfont" style="width: 300px">Tổng Tiền</th>
                                        <th class="addfont" style="width: 200px">Chi Tiết</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $stt = 1;
                                    $sql_top5 = "SELECT tk.id, tk.hoten, tk.taikhoan, tk.anh, tk.phanquyen, 
                                                COUNT(dh.id) as so_don, 
                                                SUM(dh.tongtien) as tong_tien
                                         FROM taikhoan tk
                                         LEFT JOIN donhang dh ON tk.id = dh.id_taikhoan AND dh.status = 3
                                         GROUP BY tk.id
                                         ORDER BY tong_tien DESC
                                         LIMIT 5";
                                    $top_accounts = selectAll($sql_top5, []);
                                    foreach ($top_accounts as $row) {
                                        $tong_tien = $row['tong_tien'] ?? 0;
                                    ?>
                                        <tr class="addfont">
                                            <td><?= $stt++ ?></td>
                                            <td>
                                                <img src="<?= empty($row['anh']) ? '../img/account/user.png' : '../img/account/' . htmlspecialchars($row['anh']) ?>" alt="image">
                                                <span><?= htmlspecialchars($row['hoten']) ?></span>
                                            </td>
                                            <td><?= htmlspecialchars($row['taikhoan']) ?></td>
                                            <td><?= $row['phanquyen'] == 1 ? 'Admin' : 'Khách hàng' ?></td>
                                            <td><?= $row['so_don'] ?></td>
                                            <td><?= number_format($tong_tien) ?>đ</td>
                                            <td>
                                                <a type="button" class="btn btn-primary btn-icon-text" href="user_orders.php?from_date=<?= urlencode($default_from_date) ?>&to_date=<?= urlencode($default_to_date) ?>&customer_id=<?= $row['id'] ?>">
                                                    <i class="mdi mdi-file-check btn-icon-prepend"></i> Chi Tiết
                                                </a>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php
        } else {
            echo "<p style='color: red; display: none;'>Debug: Bạn không có quyền admin (phanquyen != 1)</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red; display: none;'>Debug: Lỗi truy vấn tài khoản: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red; display: none;'>Debug: Bạn chưa đăng nhập (cookie 'user' không tồn tại)</p>";
}
?>