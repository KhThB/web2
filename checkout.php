<?php
session_start(); // Add this to initialize the session
include_once './connect.php';

// Chuyển hướng về giỏ hàng nếu giỏ hàng trống
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit();
}

// Lấy thông tin mặc định từ tài khoản
$hoten_macdinh = '';
$sdt_macdinh = '';
$diachi_macdinh = '';
if (isset($_COOKIE["user"])) {
    $taikhoan = $_COOKIE["user"];
    $taikhoan_rows = selectAll("SELECT * FROM taikhoan WHERE taikhoan=?", [$taikhoan]);
    if (!empty($taikhoan_rows)) {
        $hoten_macdinh = $taikhoan_rows[0]['hoten'] ?? '';
        $sdt_macdinh = $taikhoan_rows[0]['sdt'] ?? '';
        $diachi_macdinh = $taikhoan_rows[0]['diachi'] ?? '';
    }
}

// Xử lý thanh toán
$order_success = false; // Biến để kiểm tra trạng thái đặt hàng
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $hoten = trim($_POST['hoten'] ?? '');
    $sdt = trim($_POST['sdt'] ?? '');
    $diachi = trim($_POST['diachi'] ?? '');
    $use_new_info = isset($_POST['use_new_info']) && $_POST['use_new_info'] == '1';
    $payment_method = $_POST['payment_method'] ?? '';

    // Nếu không chọn thông tin mới, sử dụng thông tin mặc định
    if (!$use_new_info) {
        $hoten = $hoten_macdinh ?: 'Khách hàng không xác định';
        $sdt = $sdt_macdinh ?: 'Không có';
        $diachi = $diachi_macdinh ?: 'Không có địa chỉ';
    }

    // Kiểm tra dữ liệu đầu vào
    if (empty($hoten) || empty($sdt) || empty($diachi) || empty($payment_method)) {
        $error = "Vui lòng điền đầy đủ các thông tin bắt buộc.";
    } elseif (!preg_match('/^[0-9]{10,11}$/', $sdt)) {
        $error = "Số điện thoại không hợp lệ.";
    } else {
        // Tính tổng tiền
        $tongtien = 0;
        foreach ($_SESSION['cart'] as $item) {
            $tongtien += $item['gia'] * $item['soluong'];
        }
        $phiship = 50000; // Phí ship cố định
        $tongcong = $tongtien + $phiship;

        // Lấy ID tài khoản
        $id_taikhoan = 0;
        if (isset($_COOKIE["user"])) {
            $taikhoan = $_COOKIE["user"];
            $taikhoan_rows = selectAll("SELECT * FROM taikhoan WHERE taikhoan=?", [$taikhoan]);
            if (!empty($taikhoan_rows)) {
                $id_taikhoan = $taikhoan_rows[0]['id'];
            }
        }

        try {
            // Kiểm tra đơn hàng hiện có (status=1)
            $existing_order = selectAll("SELECT * FROM donhang WHERE id_taikhoan=? AND status=1 LIMIT 1", [$id_taikhoan]);
            if (!empty($existing_order)) {
                $donhang_id = $existing_order[0]['id'];
                $stmt = $conn->prepare("UPDATE donhang SET diachi=?, tongtien=?, thoigian=NOW(), status=2 WHERE id=?");
                $stmt->execute([$diachi, $tongcong, $donhang_id]);
                exSQL("DELETE FROM ctdonhang WHERE id_donhang=?", [$donhang_id]); // Xóa chi tiết cũ
            } else {
                $stmt = $conn->prepare("INSERT INTO donhang (id_taikhoan, diachi, tongtien, status, thoigian) VALUES (?, ?, ?, 2, NOW())");
                $stmt->execute([$id_taikhoan, $diachi, $tongcong]);
                $donhang_id = $conn->lastInsertId();
            }

            // Thêm chi tiết đơn hàng
            $stmt_ct = $conn->prepare("INSERT INTO ctdonhang (id_donhang, id_sanpham, soluong, gia) VALUES (?, ?, ?, ?)");
            foreach ($_SESSION['cart'] as $item) {
                $stmt_ct->execute([$donhang_id, $item['masp'], $item['soluong'], $item['gia']]);
            }

            // Xóa giỏ hàng
            unset($_SESSION['cart']);
            $order_success = true; // Đặt hàng thành công
        } catch (PDOException $e) {
            $error = "Lỗi xử lý đơn hàng: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Đặt Hàng | Smobile</title>
    <link rel="icon" href="img/logos.png">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/animate.css">
    <link rel="stylesheet" href="css/owl.carousel.min.css">
    <link rel="stylesheet" href="css/nice-select.css">
    <link rel="stylesheet" href="css/all.css">
    <link rel="stylesheet" href="css/flaticon.css">
    <link rel="stylesheet" href="css/themify-icons.css">
    <link rel="stylesheet" href="css/magnific-popup.css">
    <link rel="stylesheet" href="css/slick.css">
    <link rel="stylesheet" href="css/price_rangs.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .header_bg {
            background-color: #ecfdff;
            height: 230px;
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
        }
        .padding_top {
            padding-top: 20px;
        }
        .a1 {
            padding-top: 130px;
        }
        .a2 {
            height: 230px;
        }
        .checkout-container {
            max-width: 1200px;
            margin: 50px auto;
        }
        .order-summary {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
        }
        .form-section {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .payment-methods label {
            margin-right: 20px;
        }
        .error-message {
            color: red;
            font-size: 0.9em;
        }
        .default-info {
            background-color: #f1f1f1;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <!--================Home Banner Area =================-->
    <section class="breadcrumb header_bg">
        <div class="container">
            <div class="row justify-content-center a2">
                <div class="col-lg-8 a2">
                    <div class="a1">
                        <h2>Đặt Hàng</h2>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!--================Checkout Area =================-->
    <section class="checkout_area padding_top">
        <div class="container checkout-container">
            <?php if ($order_success): ?>
                <div class="text-center">
                    <h2>Cảm ơn bạn đã đặt hàng!</h2>
                    <p>Đơn hàng của bạn đã được ghi nhận. Chúng tôi sẽ xử lý sớm nhất có thể.</p>
                    <a href="index.php" class="btn btn-primary">Quay về trang chủ</a>
                </div>
            <?php else: ?>
                <h2 class="mb-4 text-center">Thanh Toán Đơn Hàng</h2>
                <div class="row">
                    <!-- Tóm tắt đơn hàng -->
                    <div class="col-md-5 order-summary mb-4">
                        <h4>Tóm Tắt Đơn Hàng</h4>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Sản phẩm</th>
                                    <th>Số lượng</th>
                                    <th>Thành tiền</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $tongtien = 0;
                                // Kiểm tra lại $_SESSION['cart'] trước khi sử dụng
                                if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
                                    foreach ($_SESSION['cart'] as $item):
                                        $thanhtien = $item['gia'] * $item['soluong'];
                                        $tongtien += $thanhtien;
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['tensp']) ?></td>
                                    <td><?= $item['soluong'] ?></td>
                                    <td><?= number_format($thanhtien) ?>đ</td>
                                </tr>
                                <?php endforeach; ?>
                                <tr>
                                    <th colspan="2">Tổng</th>
                                    <th><?= number_format($tongtien) ?>đ</th>
                                </tr>
                                <tr>
                                    <th colspan="2">Phí ship</th>
                                    <th><?= number_format($phiship = 50000) ?>đ</th>
                                </tr>
                                <tr>
                                    <th colspan="2">Tổng cộng</th>
                                    <th><?= number_format($tongtien + $phiship) ?>đ</th>
                                </tr>
                                <?php } else { ?>
                                <tr>
                                    <td colspan="3" class="text-center">Giỏ hàng của bạn đang trống.</td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Biểu mẫu thanh toán -->
                    <div class="col-md-7 form-section">
                        <h4>Thông Tin Thanh Toán</h4>
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label">Thông Tin Nhận Hàng <span class="text-danger">*</span></label>
                                <div class="default-info">
                                    <strong>Họ và Tên:</strong> <?= $hoten_macdinh ? htmlspecialchars($hoten_macdinh) : 'Chưa có thông tin' ?><br>
                                    <strong>Số Điện Thoại:</strong> <?= $sdt_macdinh ? htmlspecialchars($sdt_macdinh) : 'Chưa có thông tin' ?><br>
                                    <strong>Địa Chỉ:</strong> <?= $diachi_macdinh ? htmlspecialchars($diachi_macdinh) : 'Chưa có thông tin' ?>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="use_new_info" name="use_new_info" value="1">
                                    <label class="form-check-label" for="use_new_info">Nhập thông tin nhận hàng mới</label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="hoten" class="form-label">Họ và Tên <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="hoten" name="hoten" value="<?= isset($hoten) && isset($use_new_info) ? htmlspecialchars($hoten) : '' ?>" disabled required>
                            </div>
                            <div class="mb-3">
                                <label for="sdt" class="form-label">Số Điện Thoại <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="sdt" name="sdt" value="<?= isset($sdt) && isset($use_new_info) ? htmlspecialchars($sdt) : '' ?>" disabled required>
                            </div>
                            <div class="mb-3">
                                <label for="diachi" class="form-label">Địa Chỉ Nhận Hàng <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="diachi" name="diachi" rows="4" disabled required><?= isset($diachi) && isset($use_new_info) ? htmlspecialchars($diachi) : '' ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Phương Thức Thanh Toán <span class="text-danger">*</span></label>
                                <div class="payment-methods">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_method" id="cod" value="cod" checked>
                                        <label class="form-check-label" for="cod">Thanh toán khi nhận hàng (COD)</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_method" id="bank" value="bank">
                                        <label class="form-check-label" for="bank">Chuyển khoản ngân hàng</label>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Xác Nhận Đặt Hàng</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>
    <!--================End Checkout Area =================-->

    <?php include 'footer.php'; ?>
    <script src="js/jquery-1.12.1.min.js"></script>
    <script src="js/popper.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/jquery.magnific-popup.js"></script>
    <script src="js/swiper.min.js"></script>
    <script src="js/masonry.pkgd.js"></script>
    <script src="js/owl.carousel.min.js"></script>
    <script src="js/jquery.nice-select.min.js"></script>
    <script src="js/slick.min.js"></script>
    <script src="js/jquery.counterup.min.js"></script>
    <script src="js/waypoints.min.js"></script>
    <script src="js/contact.js"></script>
    <script src="js/jquery.ajaxchimp.min.js"></script>
    <script src="js/jquery.form.js"></script>
    <script src="js/jquery.validate.min.js"></script>
    <script src="js/mail-script.js"></script>
    <script src="js/stellar.js"></script>
    <script src="js/price_rangs.js"></script>
    <script src="js/custom.js"></script>
    <script>
        // Bật/tắt các ô nhập thông tin mới dựa trên checkbox
        document.getElementById('use_new_info').addEventListener('change', function() {
            const fields = [
                document.getElementById('hoten'),
                document.getElementById('sdt'),
                document.getElementById('diachi')
            ];
            if (this.checked) {
                fields.forEach(field => {
                    field.disabled = false;
                    field.focus();
                });
            } else {
                fields.forEach(field => {
                    field.disabled = true;
                    field.value = '';
                });
            }
        });
    </script>
</body>
</html>