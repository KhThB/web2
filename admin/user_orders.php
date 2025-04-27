<?php
include 'header.php';

// Kiểm tra cookie user
if (!isset($_COOKIE["user"])) {
    header('Location: ../login.php');
    exit;
}

$user = $_COOKIE["user"];
$sql = "SELECT * FROM taikhoan WHERE taikhoan = ?";
try {
    $result = selectAll($sql, [$user]);
    $permission = 0;
    foreach ($result as $row) {
        $permission = $row['phanquyen'];
    }

    if ($permission != 1) {
        include '404.php';
        exit;
    }

    // Lấy tham số từ URL
    $from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
    $to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';
    $customer_id = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : 0;
    $customer_name = '';
    $orders = [];
    $debug_message = '';

    if ($customer_id && $from_date && $to_date) {
        // Lấy tên khách hàng
        $sql_customer = "SELECT hoten FROM taikhoan WHERE id = ?";
        $customer_data = selectAll($sql_customer, [$customer_id]);
        if (!empty($customer_data)) {
            $customer_name = htmlspecialchars($customer_data[0]['hoten']);
        } else {
            $debug_message = "Không tìm thấy khách hàng với ID: $customer_id.";
        }

        // Chuyển định dạng thời gian sang DD-MM-YYYY HH:MM:SS
        $from_date_sql = date('d-m-Y 00:00:00', strtotime($from_date));
        $to_date_sql = date('d-m-Y 23:59:59', strtotime($to_date));

        // Truy vấn đơn hàng
        $sql_orders = "SELECT * FROM donhang 
                       WHERE id_taikhoan = ? AND status = 3 
                       AND thoigian BETWEEN ? AND ?";
        $orders = selectAll($sql_orders, [$customer_id, $from_date_sql, $to_date_sql]);
        
        // Debug: Kiểm tra số lượng đơn hàng
        $debug_message .= " Số đơn hàng tìm thấy: " . count($orders);
    } else {
        $debug_message = "Thiếu tham số customer_id, from_date hoặc to_date.";
    }
} catch (Exception $e) {
    echo "<p style='color: red; display: none;'>Debug: Lỗi truy vấn: " . $e->getMessage() . "</p>";
    include '404.php';
    exit;
}
?>
<!-- Nội dung giao diện -->
<div class="main-panel">
    <div class="content-wrapper">
        <!-- Debug message (ẩn trong sản phẩm thật) -->
        <p style="color: red; display: none;">Debug: <?= htmlspecialchars($debug_message) ?></p>

        <div class="row">
            <div class="col-12 grid-margin">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title addfont">Danh Sách Đơn Hàng Của: <?= $customer_name ?: 'Không xác định' ?></h4>
                        <p class="addfont">Khoảng thời gian: <?= htmlspecialchars($from_date) ?> đến <?= htmlspecialchars($to_date) ?></p>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th class="addfont" style="width: 10px">STT</th>
                                        <th class="addfont" style="width: 200px">ID Đơn Hàng</th>
                                        <th class="addfont" style="width: 300px">Tổng Tiền</th>
                                        <th class="addfont" style="width: 400px">Thời Gian Đặt Hàng</th>
                                        <th class="addfont" style="width: 200px">Trạng Thái</th>
                                        <th class="addfont" style="width: 200px">Chi Tiết</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($orders)) { ?>
                                        <tr>
                                            <td colspan="6" class="addfont text-danger">Không tìm thấy đơn hàng nào trong khoảng thời gian này. Vui lòng kiểm tra lại dữ liệu hoặc khoảng thời gian.</td>
                                        </tr>
                                    <?php } else { ?>
                                        <?php $order_stt = 1; ?>
                                        <?php foreach ($orders as $order) { ?>
                                            <tr class="addfont">
                                                <td><?= $order_stt++ ?></td>
                                                <td><?= $order['id'] ?></td>
                                                <td><?= number_format($order['tongtien']) ?>đ</td>
                                                <td>
                                                    <p style="white-space: nowrap;overflow: hidden;text-overflow: ellipsis;max-width: 200px; padding-top: 12px;"><?= htmlspecialchars($order['thoigian']) ?></p>
                                                </td>
                                                <td>
                                                    <span class="badge badge-success">Đã Giao</span>
                                                </td>
                                                <td>
                                                    <a type="button" class="btn btn-primary btn-icon-text" href="./cartdetail.php?id=<?= $order['id'] ?>" onclick="console.log('Redirecting to: ./cartdetail.php?id=<?= $order['id'] ?>')">
                                                        <i class="mdi mdi-file-check btn-icon-prepend"></i> Chi Tiết
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                        <!-- Nút quay lại -->
                        <a href="index.php" class="btn btn-secondary mt-3 addfont">Quay lại</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include 'footer.php'; ?>
</div>
<?php
// Đóng thẻ PHP
?>